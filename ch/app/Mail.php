<?php
declare(strict_types=1);
namespace Cosmos;
if(isset($_SERVER['SCRIPT_FILENAME'])&&realpath((string)$_SERVER['SCRIPT_FILENAME'])===__FILE__){http_response_code(404);exit;}
final class Mail {
    public static function send(array $config,string $recipient,string $subject,string $body): void {
        $host=$config['host'];$port=(int)$config['port'];$from=$config['from'];
        if(!preg_match('/^[a-zA-Z0-9.-]+$/',$host)||$port<1||$port>65535||!$config['user']||!$config['password']||!filter_var($from,FILTER_VALIDATE_EMAIL)||!filter_var($recipient,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('SMTP configuration unavailable');
        $context=stream_context_create(['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'peer_name'=>$host]]);
        $transport=($config['security']??'starttls')==='tls'?'tls://':'tcp://';
        $stream=@stream_socket_client($transport.$host.':'.$port,$errno,$errstr,10,STREAM_CLIENT_CONNECT,$context);
        if(!$stream)throw new \RuntimeException('SMTP connection failed');
        stream_set_timeout($stream,10);
        $read=static function()use($stream):string{$text='';do{$line=fgets($stream,2048);if($line===false)throw new \RuntimeException('SMTP response failed');$text.=$line;}while(isset($line[3])&&$line[3]==='-');return $text;};
        $send=static function(string $command,array $codes)use($stream,$read):void{if(fwrite($stream,$command."\r\n")===false)throw new \RuntimeException('SMTP write failed');$reply=$read();if(!in_array((int)substr($reply,0,3),$codes,true))throw new \RuntimeException('SMTP rejected command');};
        try {
            if((int)substr($read(),0,3)!==220)throw new \RuntimeException('SMTP greeting failed');
            $send('EHLO cosmos-house',[250]);
            if($transport==='tcp://'){$send('STARTTLS',[220]);if(!stream_socket_enable_crypto($stream,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new \RuntimeException('SMTP TLS failed');$send('EHLO cosmos-house',[250]);}
            $send('AUTH PLAIN '.base64_encode("\0".$config['user']."\0".$config['password']),[235]);
            $send('MAIL FROM:<'.$from.'>',[250]);$send('RCPT TO:<'.$recipient.'>',[250,251]);$send('DATA',[354]);
            $headers='From: Cosmos House <'.$from.">\r\nTo: <".$recipient.">\r\nSubject: =?UTF-8?B?".base64_encode($subject)."?=\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n";
            $send($headers."\r\n".chunk_split(base64_encode($body),76,"\r\n")."\r\n.",[250]);$send('QUIT',[221]);
        }finally{fclose($stream);}
    }
}
