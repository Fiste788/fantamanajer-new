<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Role Entity
 *
 * @property int $id
 * @property string $singolar
 * @property string $plural
 * @property string $abbreviation
 * @property string $determinant
 *
 * @property \App\Model\Entity\Member[] $members
 */
class Role extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'singolar' => true,
        'plural' => true,
        'abbreviation' => true,
        'determinant' => true,
        'members' => false,
    ];
}
