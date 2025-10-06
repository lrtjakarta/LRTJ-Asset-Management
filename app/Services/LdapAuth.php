<?php

namespace App\Services;

class LdapAuth
{
    public function connect(string $host, int $port, int $timeout)
    {
        $conn = @ldap_connect($host, $port);
        if (!$conn) return null;
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, $timeout);
        return $conn;
    }

    public function bindDn(string $host, int $port, string $dn, string $password, int $timeout): bool
    {
        $conn = $this->connect($host, $port, $timeout);
        if (!$conn) return false;
        $ok = @ldap_bind($conn, $dn, $password);
        @ldap_unbind($conn);
        return (bool) $ok;
    }

    public function findUserDn(string $host, int $port, ?string $bindDn, ?string $bindPass,
                               string $baseDn, string $username, int $timeout): ?string
    {
        $conn = $this->connect($host, $port, $timeout);
        if (!$conn) return null;

        if ($bindDn && $bindPass) {
            if (!@ldap_bind($conn, $bindDn, $bindPass)) { @ldap_unbind($conn); return null; }
        } else {
            @ldap_bind($conn);
        }

        $filter = sprintf('(uid=%s)', ldap_escape($username, '', LDAP_ESCAPE_FILTER));
        $sr = @ldap_search($conn, $baseDn, $filter, ['dn'], 0, 1);
        if (!$sr) { @ldap_unbind($conn); return null; }

        $entries = @ldap_get_entries($conn, $sr);
        @ldap_unbind($conn);

        return ($entries && $entries['count'] > 0) ? ($entries[0]['dn'] ?? null) : null;
    }

    public function fetchAttributes(string $host, int $port, ?string $bindDn, ?string $bindPass,
                                    string $baseDn, string $username, int $timeout): array
    {
        $conn = $this->connect($host, $port, $timeout);
        if (!$conn) return [];

        if ($bindDn && $bindPass) {
            if (!@ldap_bind($conn, $bindDn, $bindPass)) { @ldap_unbind($conn); return []; }
        } else {
            @ldap_bind($conn);
        }

        $filter = sprintf('(uid=%s)', ldap_escape($username, '', LDAP_ESCAPE_FILTER));
        $attrs  = ['cn','uid','mail','memberOf','sn','givenName','ou'];
        $sr     = @ldap_search($conn, $baseDn, $filter, $attrs, 0, 1);
        if (!$sr) { @ldap_unbind($conn); return []; }

        $entries = @ldap_get_entries($conn, $sr);
        @ldap_unbind($conn);

        return ($entries && $entries['count'] > 0) ? $entries[0] : [];
    }
}
