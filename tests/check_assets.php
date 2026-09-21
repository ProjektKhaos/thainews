<?php
// Senast uppdaterad: 2026-09-20 18:50 | Standalone asset integrity check
$root=dirname(__DIR__);
$expected=[
 'img/thainews_logo1.png'=>'ab2562cbb4f143806d0151ab7eaae8595634456539b7af4fb438c623e5a12758',
 'img/thainews_fb_og.png'=>'603e9b15979804061d501e7349f0e3def0e2d475c1db05305deb606204af6b9e',
];
$ok=true;foreach($expected as $file=>$hash){$actual=hash_file('sha256',$root.'/'.$file);$same=hash_equals($hash,$actual);printf("%s %s\n",$same?'OK':'FAIL',$file);$ok=$ok&&$same;}exit($ok?0:1);
