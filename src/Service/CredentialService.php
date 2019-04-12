<?php

namespace App\Service;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Burzum\Cake\Service\ServiceAwareTrait;
use Cake\Datasource\ModelAwareTrait;
use Cake\Http\Client as CakeClient;
use Cake\Utility\Hash;
use CBOR\Decoder;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;
use Cose\Algorithms;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Http\Adapter\Cake\Client as CakeAdapter;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AndroidSafetyNetAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;

/**
 * Credentials Repo
 *
 * @property CredentialRepositoryService $CredentialRepository
 * @property UsersTable $Users
 */
class CredentialService
{
    use ModelAwareTrait;
    use ServiceAwareTrait;

    /**
     * Costructor
     */
    public function __construct()
    {
        $this->loadModel('Users');
        $this->loadService('CredentialRepository');
    }

    /**
     * Undocumented function
     *
     * @param \App\Model\Entity\User $user User
     * @return PublicKeyCredentialUserEntity
     */
    public function createUserEntity(User $user)
    {
        // User Entity
        return new PublicKeyCredentialUserEntity(
            (string)$user->id,
            (string)$user->id,
            $user->name . ' ' . $user->surname,
            null
        );
    }

    /**
     * Undocumented function
     *
     * @return AuthenticationExtensionsClientInputs
     */
    private function getExtensions()
    {
        $extensions = new AuthenticationExtensionsClientInputs();
        $extensions->add(new AuthenticationExtension('loc', true));

        return $extensions;
    }

    /**
     * Undocumented function
     *
     * @return PublicKeyCredentialRpEntity
     */
    private function createRpEntity()
    {
        return new PublicKeyCredentialRpEntity(
            'FantaManajer', //Name
            //'fantamanajer.it', //ID
            'localhost',
            null //Icon
        );
    }

    /**
     * Undocumented function
     *
     * @return Decoder
     */
    private function createDecoder()
    {
        // Create a CBOR Decoder object
        $otherObjectManager = new OtherObjectManager();
        $tagObjectManager = new TagObjectManager();

        return new Decoder($tagObjectManager, $otherObjectManager);
    }

    /**
     * Undocumented function
     *
     * @param PublicKeyCredentialSource[] $credentials credentials
     * @return PublicKeyCredentialDescriptor[]
     */
    private function credentialsToDescriptors(array $credentials)
    {
        return Hash::map($credentials, '{*}', function (PublicKeyCredentialSource $value) {
            return $value->getPublicKeyCredentialDescriptor();
        });
    }

    /**
     * Undocumented function
     *
     * @return Manager
     */
    private function createAlgorithManager()
    {
        // Cose Algorithm Manager
        $coseAlgorithmManager = new Manager();
        $coseAlgorithmManager->add(new ECDSA\ES256());
        $coseAlgorithmManager->add(new ECDSA\ES512());
        $coseAlgorithmManager->add(new EdDSA\EdDSA());
        $coseAlgorithmManager->add(new RSA\RS1());
        $coseAlgorithmManager->add(new RSA\RS256());
        $coseAlgorithmManager->add(new RSA\RS512());

        return $coseAlgorithmManager;
    }

    /**
     * Undocumented function
     *
     * @param Decoder $decoder arg
     * @return AttestationStatementSupportManager
     */
    private function createStatementSupportManager(Decoder $decoder)
    {
        $config = [
            // Config params
        ];
        $cakeClient = new CakeClient($config);
        $adapter = new CakeAdapter($cakeClient, new GuzzleMessageFactory());

        $coseAlgorithmManager = $this->createAlgorithManager();

        $attestationStatementSupportManager = new AttestationStatementSupportManager();
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
        $attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport($decoder));
        $attestationStatementSupportManager->add(new AndroidSafetyNetAttestationStatementSupport($adapter, 'AIzaSyA9hQpKqE3N8D5Zz09DzrT421T9UZc23iM00'));
        $attestationStatementSupportManager->add(new AndroidKeyAttestationStatementSupport($decoder));
        $attestationStatementSupportManager->add(new TPMAttestationStatementSupport());
        $attestationStatementSupportManager->add(new PackedAttestationStatementSupport($decoder, $coseAlgorithmManager));

        return $attestationStatementSupportManager;
    }

    /**
     * Undocumented function
     *
     * @param ServerRequestInterface $request Request
     * @return PublicKeyCredentialRequestOptions
     */
    public function assertionRequest(ServerRequestInterface $request)
    {
        // List of registered PublicKeyCredentialDescriptor classes associated to the user
        $params = $request->getQueryParams();
        $user = $this->Users->find()->where(['email' => $params['email']])->first();
        $credentialUser = $user->toCredentialUserEntity();
        $credentials = $this->CredentialRepository->findAllForUserEntity($credentialUser);
        $registeredPublicKeyCredentialDescriptors = $this->credentialsToDescriptors($credentials);

        // Public Key Credential Request Options
        $publicKeyCredentialRequestOptions = new PublicKeyCredentialRequestOptions(
            random_bytes(32),
            60000,
            'localhost',
            $registeredPublicKeyCredentialDescriptors,
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            $this->getExtensions()
        );
        $request->getSession()->start();
        $request->getSession()->write("User.Handle", $credentialUser->getId());
        $request->getSession()->write("User.PublicKey", json_encode($publicKeyCredentialRequestOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $publicKeyCredentialRequestOptions;
    }

    /**
     * Undocumented function
     *
     * @param ServerRequestInterface $request Request
     * @return bool
     */
    public function assertionResponse(ServerRequestInterface $request)
    {
        $publicKey = $request->getSession()->consume("User.PublicKey");
        $json = \Safe\json_decode($publicKey, true);
        $publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::createFromJson($json);

        $decoder = $this->createDecoder();
        $attestationStatementSupportManager = $this->createStatementSupportManager($decoder);
        // Attestation Object Loader
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager, $decoder);

        // Public Key Credential Loader
        $publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader, $decoder);

        // Authenticator Assertion Response Validator
        $authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
            $this->CredentialRepository,
            $decoder,
            new TokenBindingNotSupportedHandler(),
            new ExtensionOutputCheckerHandler()
        );

        try {
            // Load the data
            $publicKeyCredential = $publicKeyCredentialLoader->loadArray($request->getParsedBody());
            $response = $publicKeyCredential->getResponse();

            // Check if the response is an Authenticator Assertion Response
            if (!$response instanceof AuthenticatorAssertionResponse) {
                throw new \RuntimeException('Not an authenticator assertion response');
            }

            // Check the response against the attestation request
            $authenticatorAssertionResponseValidator->check(
                $publicKeyCredential->getRawId(),
                $response,
                $publicKeyCredentialRequestOptions,
                $request,
                $request->getSession()->consume('User.Handle')
            );

            return true;
        } catch (\Throwable $throwable) {
            throw $throwable;
        }
    }

    /**
     * Undocumented function
     *
     * @param ServerRequestInterface $request Request
     * @return PublicKeyCredentialCreationOptions
     */
    public function attestationRequest(ServerRequestInterface $request)
    {
        $rpEntity = $this->createRpEntity();
        $user = $request->getAttribute('identity');
        if ($user->uuid == null) {
            $user->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $this->Users->save($user);
        }
        $userEntity = $user->toCredentialUserEntity();

        $credential = $this->CredentialRepository->findAllForUserEntity($userEntity);
        $excludeCredentials = $this->credentialsToDescriptors($credential);

        // Public Key Credential Parameters
        $publicKeyCredentialParametersList = [
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_RS256),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_RS384),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_RS512),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_ES256),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_ES384),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_ES512),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_PS256),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_PS384),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_PS512)
        ];

        // Authenticator Selection Criteria (we used default values)
        $authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria(AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE, false, AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED);
        //$authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria();

        $publicKeyCredentialCreationOptions = new PublicKeyCredentialCreationOptions(
            $rpEntity,
            $userEntity,
            random_bytes(32),
            $publicKeyCredentialParametersList,
            20000,
            $excludeCredentials,
            $authenticatorSelectionCriteria,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $this->getExtensions()
        );

        $session = $request->getSession();
        $session->start();
        $session->write("User.PublicKey", \Safe\json_encode($publicKeyCredentialCreationOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $publicKeyCredentialCreationOptions;
    }

    /**
     * Save the credential
     *
     * @param  ServerRequestInterface $request Request
     * @return bool
     */
    public function attestationResponse(ServerRequestInterface $request)
    {
        $publicKey = $request->getSession()->consume("User.PublicKey");
        $json = \Safe\json_decode($publicKey, true);
        $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::createFromJson($json);

        $decoder = $this->createDecoder();

        // Attestation Statement Support Manager
        $attestationStatementSupportManager = $this->createStatementSupportManager($decoder);

        // Attestation Object Loader
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager, $decoder);

        // Public Key Credential Loader
        $publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader, $decoder);

        $authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
            $attestationStatementSupportManager,
            $this->CredentialRepository,
            new TokenBindingNotSupportedHandler(),
            new ExtensionOutputCheckerHandler()
        );

        try {
            // Load the data
            $publicKeyCredential = $publicKeyCredentialLoader->loadArray($request->getParsedBody());
            $response = $publicKeyCredential->getResponse();

            // Check if the response is an Authenticator Attestation Response
            if (!$response instanceof AuthenticatorAttestationResponse) {
                throw new \RuntimeException('Not an authenticator attestation response');
            }

            // Check the response against the request
            $authenticatorAttestationResponseValidator->check($response, $publicKeyCredentialCreationOptions, $request);

            $credentialSource = PublicKeyCredentialSource::createFromPublicKeyCredential(
                $publicKeyCredential,
                $publicKeyCredentialCreationOptions->getUser()->getId()
            );

            $credential = $this->CredentialRepository->PublicKeyCredentialSources->newEntity();
            $credential->fromCredentialSource($credentialSource);
            $this->CredentialRepository->PublicKeyCredentialSources->save($credential);

            return true;
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}