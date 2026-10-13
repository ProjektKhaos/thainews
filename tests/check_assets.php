<?php
// Senast uppdaterad: 2026-09-20 18:50 | Standalone asset integrity check
$root=dirname(__DIR__);
$expected=[
 'img/thainews_logo1.png'=>'ab2562cbb4f143806d0151ab7eaae8595634456539b7af4fb438c623e5a12758',
 'img/fb_og.png'=>'a4142fb25dc2909db81dfa45c70bc935c4e7f8285a0a4baf61fa9621ed2a48ef',
];
$ok=true;foreach($expected as $file=>$hash){$actual=hash_file('sha256',$root.'/'.$file);$same=hash_equals($hash,$actual);printf("%s %s\n",$same?'OK':'FAIL',$file);$ok=$ok&&$same;}exit($ok?0:1);
