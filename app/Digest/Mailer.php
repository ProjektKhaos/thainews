<?php
// Senast uppdaterad: 2026-09-20 18:18 | Symfony Mailer adapter

declare(strict_types=1);
namespace ThaiNews\Digest;

use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use ThaiNews\Config;

class Mailer
{
    private ?SymfonyMailer $mailer=null;
    public function __construct(private Config $config)
    {
        if ((bool)$config->get('smtp.enabled',false) && class_exists(Transport::class)) {
            $this->mailer=new SymfonyMailer(Transport::fromDsn($config->requireString('smtp.dsn')));
        }
    }
    protected function send(Email $email): void
    {
        if (!$this->mailer) throw new \RuntimeException('SMTP delivery is not configured.');
        $this->mailer->send($email);
    }
    public function sendConfirmation(string $to, string $language, string $link): void
    {
        $subject=['en'=>'Confirm your Thai News subscription','th'=>'ยืนยันการสมัครรับข่าว Thai News','sv'=>'Bekräfta din prenumeration på Thai News'][$language] ?? 'Confirm your Thai News subscription';
        $body=['en'=>'Confirm your subscription by opening this link: ','th'=>'ยืนยันการสมัครรับข่าวสารโดยเปิดลิงก์นี้: ','sv'=>'Bekräfta din prenumeration genom att öppna länken: '][$language] ?? '';
        $logo=$this->publicUrl('img/thainews_logo1.png');
        $button=['en'=>'Confirm subscription','th'=>'ยืนยันการสมัคร','sv'=>'Bekräfta prenumeration'][$language] ?? 'Confirm subscription';
        $safeBody=htmlspecialchars($body,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $safeLink=htmlspecialchars($link,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $html='<!doctype html><html><body style="margin:0;background:#f5f7fa"><div style="max-width:640px;margin:auto;padding:24px;font-family:Arial,sans-serif;color:#142236"><div style="background:#fff;border:1px solid #e4e9ef;border-radius:16px;padding:28px"><img src="'.htmlspecialchars($logo,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'" alt="Thai News" width="110" style="display:block;width:110px;height:auto;margin:0 0 20px"><h1 style="font-size:24px;margin:0 0 16px">'.htmlspecialchars($subject,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</h1><p>'.$safeBody.'</p><p><a href="'.$safeLink.'" style="display:inline-block;background:#073f7f;color:#fff;text-decoration:none;font-weight:bold;padding:12px 18px;border-radius:8px">'.htmlspecialchars($button,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</a></p><p style="font-size:12px;color:#66758a;word-break:break-all">'.$safeLink.'</p></div></div></body></html>';
        $email=(new Email())->from($this->from())->to($to)->subject($subject)->text($body.$link)->html($html);
        $this->send($email);
    }
    public function sendDigest(string $to,string $subject,string $html,string $text): ?string
    {
        $email=(new Email())->from($this->from())->to($to)->subject($subject)->html($html)->text($text);
        $this->send($email); return $email->getHeaders()->get('Message-ID')?->getBodyAsString();
    }

    private function from(): Address
    {
        return new Address($this->config->requireString('smtp.from_email'), (string)$this->config->get('smtp.from_name','Thai News'));
    }

    private function publicUrl(string $path): string
    {
        $origin=rtrim($this->config->requireString('public_origin'),'/');
        $base=trim((string)$this->config->get('base_url',''),'/');
        return $origin . ($base===''?'':'/'.$base) . '/' . ltrim($path,'/');
    }
}
