<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Team;
use Authorization\IdentityInterface;

class TeamPolicy
{
    public function canView(IdentityInterface $user, Team $team)
    {
        return $user->isInChampionship($team->championship_id);
    }

    public function canAdd(IdentityInterface $user, Team $team)
    {
        return $user->admin || ($user->isChampionshipAdmin($team->championship_id));
    }

    public function canEdit(IdentityInterface $user, Team $team)
    {
        return $user->hasTeam($team->id) || $user->admin;
    }

    public function canDelete(IdentityInterface $user, Team $team)
    {
        return false;
    }

    public function canIndex(IdentityInterface $user, Team $team)
    {
        return $user->isInChampionship($team->championship_id);
    }
}
