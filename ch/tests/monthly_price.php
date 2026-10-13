<?php
declare(strict_types=1);
$private=sys_get_temp_dir().'/cosmos-monthly-'.bin2hex(random_bytes(8));mkdir($private,0700);
$path=$private.'/test.sqlite';putenv('COSMOS_DB_PATH='.$path);putenv('COSMOS_DOCUMENT_ROOT='.dirname(__DIR__));
require dirname(__DIR__).'/app/Booking.php';
function assert_monthly(bool $condition,string $message): void {if(!$condition)throw new RuntimeException($message);}
try{
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/scripts/migrate.php'),$output,$code);assert_monthly($code===0,'Migration failed');
    $app=\Cosmos\Booking::connect();$app->updateSetting('max_guests','4','test');$app->updateSetting('included_guests','4','test');$app->setMonthlyRate(4400000,30,'test');
    $start=(new DateTimeImmutable('today',new DateTimeZone('Asia/Bangkok')))->modify('+10 days');
    $expected=[1=>146667,2=>293333,7=>1026667,15=>2200000,28=>4106667,29=>4253333,30=>4400000,31=>4546667,60=>8800000,90=>13200000];
    foreach($expected as $nights=>$total){
        $price=$app->price($start->format('Y-m-d'),$start->modify('+'.$nights.' days')->format('Y-m-d'),4);
        assert_monthly($price['totalSatang']===$total,'Incorrect total for '.$nights.' nights');
        assert_monthly(array_sum(array_column($price['nights'],'amountSatang'))===$total,'Nightly lines disagree with total');
        assert_monthly($price['monthlyReference']['baseNights']===$nights,'Incorrect reference night count');
    }
    assert_monthly($app->price($start->format('Y-m-d'),$start->modify('+1 day')->format('Y-m-d'),1)['totalSatang']===146667,'House price must not be multiplied by guests');
    try{$app->price($start->format('Y-m-d'),$start->modify('+1 day')->format('Y-m-d'),5);throw new RuntimeException('Too many guests accepted');}catch(\Cosmos\BookingError $e){assert_monthly($e->httpStatus===422,'Guest limit status');}
    foreach([28,29,30,31]as $reference){$app->setMonthlyRate(4400000,$reference,'test');assert_monthly($app->price($start->format('Y-m-d'),$start->modify('+'.$reference.' days')->format('Y-m-d'),4)['totalSatang']===4400000,'Reference period does not match exactly');}
    $app->setMonthlyRate(4400000,30,'test');
    $reference=$app->createBooking(['name'=>'Private fixture','check_in'=>$start->format('Y-m-d'),'check_out'=>$start->modify('+30 days')->format('Y-m-d'),'guests'=>4,'status'=>'confirmed'],'test');
    $app->setMonthlyRate(4500000,30,'test');
    $q=$app->db->prepare('SELECT total_satang,price_snapshot_json FROM bookings WHERE reference=?');$q->execute([$reference]);$saved=$q->fetch();
    assert_monthly((int)$saved['total_satang']===4400000&&json_decode($saved['price_snapshot_json'],true)['monthlyReference']['amountSatang']===4400000,'Historical price changed');
    $app->setMonthlyRate(4400000,30,'test');$far=$start->modify('+100 days');
    $q=$app->db->prepare('INSERT INTO rate_rules(label,start_date,end_date,amount_satang,priority,active) VALUES(?,?,?,?,10,1)');$q->execute(['Seasonal fixture',$far->format('Y-m-d'),$far->modify('+2 days')->format('Y-m-d'),200000]);
    $price=$app->price($far->format('Y-m-d'),$far->modify('+30 days')->format('Y-m-d'),4);
    assert_monthly($price['totalSatang']===4506667&&$price['monthlyReference']['baseNights']===28,'Seasonal rates do not replace base nights correctly');
    $app->db->exec("INSERT INTO discount_rules(label,family,min_nights,kind,value,priority,active) VALUES('Fixture','length',7,'percent',1000,10,1)");
    $outside=$far->modify('+100 days');$price=$app->price($outside->format('Y-m-d'),$outside->modify('+7 days')->format('Y-m-d'),4);assert_monthly($price['totalSatang']===924000,'Discount rounding on monthly reference failed');
    $app->db->exec('UPDATE rate_rules SET active=0');$app->db->exec('UPDATE discount_rules SET active=0');
    $quote=$app->quote($outside->format('Y-m-d'),$outside->modify('+30 days')->format('Y-m-d'),4);$oldVersion=$quote['price']['ruleVersion'];
    $app->updateSetting('base_rate_satang','200000','test');$nightly=$app->price($outside->format('Y-m-d'),$outside->modify('+30 days')->format('Y-m-d'),4);
    assert_monthly($nightly['totalSatang']===6000000&&!isset($nightly['monthlyReference'])&&$nightly['ruleVersion']>$oldVersion,'Switching back to nightly did not invalidate the pricing version');
    assert_monthly(!$app->publicOpen(),'Pricing must not open public bookings');
    assert_monthly((int)$app->db->query('SELECT COUNT(*) FROM notification_outbox')->fetchColumn()===0,'Pricing test queued email');
    echo "PASS monthly pricing: exact 30/60/90-night totals, short stays, reference periods, guest limit, seasonal overrides, discounts, snapshots and nightly mode switch\n";
}finally{foreach(glob($private.'/*')as $file)if(is_file($file))unlink($file);foreach(glob($private.'/.*')as $file)if(is_file($file))unlink($file);rmdir($private);}
