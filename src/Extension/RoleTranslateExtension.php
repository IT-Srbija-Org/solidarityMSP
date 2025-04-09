<?php

namespace App\Extension;

use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RoleTranslateExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('roleTranslate', [$this, 'roleTranslate']),
        ];
    }

    public function roleTranslate(array $roles): string
    {
        $allRoles = User::ROLES;
        if (empty($roles)) {
            return $allRoles['ROLE_USER'];
        }

        $roleUserKey = array_search('ROLE_USER', $roles);
        if (1 == count($roles)) {
            return $allRoles['ROLE_USER'];
        }

        unset($roles[$roleUserKey]);
        foreach ($roles as $key => $role) {
            $roles[$key] = User::ROLES[$role] ?? $role;
        }

        return implode(', ', $roles);
    }
}
