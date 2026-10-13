"""Verify SMTP TLS/authentication and message delivery against a local fixture only."""
import json
import pathlib
import socket
import ssl
import subprocess
import tempfile
import threading

PROJECT = pathlib.Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory(prefix='cosmos-smtp-') as directory:
    root = pathlib.Path(directory)
    cert, key = root / 'cert.pem', root / 'key.pem'
    subprocess.run(['openssl','req','-x509','-newkey','rsa:2048','-nodes','-keyout',str(key),'-out',str(cert),'-days','1','-subj','/CN=localhost','-addext','subjectAltName=DNS:localhost'],check=True,capture_output=True)
    for security in ['starttls','tls']:
        listener = socket.socket()
        listener.bind(('127.0.0.1',0))
        listener.listen(1)
        port=listener.getsockname()[1]
        errors=[]
        received=[]
        def serve():
            try:
                connection,_=listener.accept()
                connection.settimeout(10)
                context=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
                context.load_cert_chain(cert,key)
                if security=='tls': connection=context.wrap_socket(connection,server_side=True)
                stream=connection.makefile('rwb')
                def send(line): stream.write((line+'\r\n').encode());stream.flush()
                def line(): return stream.readline().decode().strip()
                send('220 fixture SMTP')
                assert line().startswith('EHLO ')
                send('250-fixture');send('250 STARTTLS')
                if security=='starttls':
                    assert line()=='STARTTLS'
                    send('220 start TLS');stream.close()
                    connection=context.wrap_socket(connection,server_side=True);stream=connection.makefile('rwb')
                    assert line().startswith('EHLO ');send('250 fixture')
                assert line().startswith('AUTH PLAIN ');send('235 authenticated')
                assert line()=='MAIL FROM:<sender@example.invalid>';send('250 ok')
                assert line()=='RCPT TO:<recipient@example.invalid>';send('250 ok')
                assert line()=='DATA';send('354 data')
                lines=[]
                while True:
                    part=stream.readline()
                    if part==b'.\r\n':break
                    if not part:raise RuntimeError('Unexpected disconnect')
                    lines.append(part)
                received.append(b''.join(lines))
                send('250 delivered');assert line()=='QUIT';send('221 bye')
                stream.close();connection.close()
            except Exception as error: errors.append(str(error))
            finally:listener.close()
        thread=threading.Thread(target=serve,daemon=True);thread.start()
        config={'host':'localhost','port':str(port),'user':'fixture','password':'fixture-only','from':'sender@example.invalid','security':security}
        code='require '+json.dumps(str(PROJECT/'app/Mail.php'))+'; $config=json_decode('+repr(json.dumps(config))+',true); \\Cosmos\\Mail::send($config,"recipient@example.invalid","Fixture subject","Fixture body");'
        result=subprocess.run(['php','-d','openssl.cafile='+str(cert),'-r',code],capture_output=True,text=True,timeout=20)
        thread.join(timeout=15)
        assert result.returncode==0,result.stderr
        assert not errors,errors
        assert len(received)==1 and b'Content-Transfer-Encoding: base64' in received[0]
        print('PASS local SMTP fixture: '+security+', verified TLS, authentication and delivery')
