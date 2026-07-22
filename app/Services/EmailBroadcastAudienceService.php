<?php

namespace App\Services;

use App\Models\EmailBroadcast;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class EmailBroadcastAudienceService
{
    /**
     * Monta a query dos destinatários de uma campanha (sempre usuários registrados).
     */
    public function query(EmailBroadcast $broadcast): Builder
    {
        return $this->buildQuery(
            audienceType: (string) $broadcast->audience_type,
            segment: $broadcast->audience_segment,
            emailList: $broadcast->email_list ?? [],
            excludeAdmins: (bool) $broadcast->exclude_admins,
        );
    }

    public function count(EmailBroadcast $broadcast): int
    {
        return $this->query($broadcast)->count();
    }

    /**
     * Conta destinatários a partir do estado bruto do formulário (email_list como texto).
     *
     * @param  array<int, mixed>|string  $emailList
     */
    public function countFromState(?string $audienceType, ?string $segment, array|string $emailList, bool $excludeAdmins): int
    {
        return $this->buildQuery($audienceType ?: 'all', $segment, $emailList, $excludeAdmins)->count();
    }

    /**
     * @param  array<int, mixed>|string  $emailList
     */
    private function buildQuery(string $audienceType, ?string $segment, array|string $emailList, bool $excludeAdmins): Builder
    {
        $query = User::query();

        switch ($audienceType) {
            case 'login_segment':
                $query->loginSegment($segment);
                break;

            case 'email_list':
                $emails = $this->normalizeEmails($emailList);
                $query->whereIn('email', $emails === [] ? ['__none__'] : $emails);
                break;

            case 'all':
            default:
                break;
        }

        if ($excludeAdmins) {
            $query->where('is_admin', false);
        }

        return $query;
    }

    /**
     * @param  array<int, mixed>|string  $rawEmails
     * @return list<string>
     */
    public function normalizeEmails(array|string $rawEmails): array
    {
        if (is_string($rawEmails)) {
            $rawEmails = preg_split('/\r\n|\r|\n/', $rawEmails) ?: [];
        }

        $valid = [];

        foreach ($rawEmails as $line) {
            $email = strtolower(trim((string) $line));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $valid[$email] = true;
        }

        return array_keys($valid);
    }
}
