<?php
// Senast uppdaterad: 2026-09-20 18:18 | Double opt-in flow

declare(strict_types=1);
namespace ThaiNews\Subscription;

use ThaiNews\Config;
use ThaiNews\Digest\Mailer;
use ThaiNews\Security\RateLimiter;

final class SubscriptionService
{
    public function __construct(private SubscriberRepository $repo, private RateLimiter $limiter, private Mailer $mailer, private Config $config) {}

    public function normalizeEmail(string $email): string
    {
        $email=trim(mb_strtolower($email));
        if (strlen($email)>254 || !str_contains($email,'@')) throw new \InvalidArgumentException('Invalid email');
        [$local,$domain]=explode('@',$email,2);
        if (function_exists('idn_to_ascii')) $domain=idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $domain;
        $email=$local.'@'.$domain;
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Invalid email');
        return $email;
    }

    public function request(string $rawEmail, string $language, string $clientFingerprint): void
    {
        $email=$this->normalizeEmail($rawEmail); $emailHash=hash('sha256',$email);
        $clientKey=$this->limiter->clientKey('subscribe',$clientFingerprint);
        if (!$this->limiter->allow('subscribe',$clientKey,5,900)) return;
        $mailKey=$this->limiter->clientKey('confirmation-mail',$emailHash);
        if (!$this->limiter->allow('confirmation-mail',$mailKey,3,86400)) return;
        $existing=$this->repo->findByEmailHash($emailHash);
        if ($existing && $existing['status']==='active') return;
        if ($existing && !empty($existing['confirmation_last_sent_at']) && strtotime((string)$existing['confirmation_last_sent_at']) > time()-900) return;
        $token=rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=');
        $id=$this->repo->upsertPending($email,$emailHash,$language,hash('sha256',$token),gmdate('Y-m-d H:i:s',time()+172800));
        $origin=rtrim($this->config->requireString('public_origin'),'/');
        $base=trim((string)$this->config->get('base_url',''),'/');
        $link=$origin . ($base===''?'':'/'.$base) . '/subscribe.php?sid=' . rawurlencode((string)$id) . '&token=' . rawurlencode($token);
        $this->mailer->sendConfirmation($email,$language,$link);
    }
}
