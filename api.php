<?php
// Backend: PHP 7.4+ , কোনো ডাটাবেস লাগে না (data/ ফোল্ডারে JSON সেভ হয়)
session_start();header('Content-Type: application/json;charset=utf-8');
$D=__DIR__.'/data/';@mkdir($D,0755,true);@mkdir(__DIR__.'/uploads',0755,true);
file_put_contents($D.'.htaccess',"Require all denied\n");
function rd($f,$d=[]){global $D;return is_file($D.$f)?json_decode(file_get_contents($D.$f),true):$d;}
function wr($f,$v){global $D;file_put_contents($D.$f,json_encode($v,JSON_UNESCAPED_UNICODE),LOCK_EX);}
function gd(){global $D;$s=json_decode(@file_get_contents(__DIR__.'/seed.json'),true)?:[];return array_merge($s,rd('districts.json'));}
function out($v){echo json_encode($v,JSON_UNESCAPED_UNICODE);exit;}
function adm(){if(empty($_SESSION['a']))out(['err'=>'login']);}
function up($k){$r=[];if(empty($_FILES[$k]))return $r;$f=$_FILES[$k];$L=is_array($f['name'])?array_keys($f['name']):[null];
 foreach($L as $i){$t=$i===null?$f['tmp_name']:$f['tmp_name'][$i];$e=$i===null?$f['error']:$f['error'][$i];
  if($e||!$t||filesize($t)>6e6)continue;$m=@getimagesize($t);$x=[1=>'gif',2=>'jpg',3=>'png',18=>'webp'][$m[2]??0]??null;if(!$x)continue;
  $n='uploads/'.bin2hex(random_bytes(8)).".$x";move_uploaded_file($t,__DIR__."/$n");$r[]=$n;}
 return $r;}
$S=rd('settings.json');
if(!$S){$S=['title'=>'Unseen Bangladesh','hero'=>'বাংলাদেশের কতটুকু ঘুরে দেখেছেন?','tag'=>'ঘোরা জেলাগুলো বেছে নিন, থিম দিন, আর ডাউনলোড করুন আপনার ভ্রমণ ম্যাপ।','pw'=>password_hash('admin123',PASSWORD_DEFAULT)];wr('settings.json',$S);}
$a=$_GET['a']??'';$p=$_POST;
$subs=rd('subs.json');
switch($a){
case 'boot':
 $ok=array_values(array_filter($subs,fn($s)=>$s['ok']));$b=[];
 foreach($ok as $s){$k=$s['contact'];$b[$k]=$b[$k]??['name'=>$s['name'],'photo'=>$s['avatar'],'pts'=>0];$b[$k]['pts']+=10;if($s['avatar'])$b[$k]['photo']=$s['avatar'];}
 usort($b,fn($x,$y)=>$y['pts']<=>$x['pts']);
 foreach($ok as &$s)unset($s['contact']);
 $s2=$S;unset($s2['pw']);out(['s'=>$s2,'g'=>gd(),'subs'=>$ok,'board'=>array_slice($b,0,10)]);
case 'geo':
 if(!is_file($D.'geo.json')){http_response_code(404);out(['err'=>'no geo']);}readfile($D.'geo.json');exit;
case 'submit':
 foreach(['name','district','contact','title','desc'] as $k)if(empty(trim($p[$k]??'')))out(['err'=>'সব * ঘর পূরণ করুন']);
 if(empty($p['consent']))out(['err'=>'অনুমতি দিন']);
 $subs[]=['id'=>uniqid(),'name'=>mb_substr($p['name'],0,60),'district'=>mb_substr($p['district'],0,60),'contact'=>mb_substr($p['contact'],0,80),
  'title'=>mb_substr($p['title'],0,100),'desc'=>mb_substr($p['desc'],0,3000),'photos'=>array_slice(up('photos'),0,10),'avatar'=>(up('avatar')[0]??''),'ok'=>false,'t'=>time()];
 wr('subs.json',$subs);out(['ok'=>1]);
case 'login':
 if(password_verify($p['pw']??'',$S['pw'])){$_SESSION['a']=1;out(['ok'=>1]);}out(['err'=>'ভুল পাসওয়ার্ড']);
case 'logout':session_destroy();out(['ok'=>1]);
case 'me':out(['a'=>!empty($_SESSION['a'])]);
case 'subs':adm();out($subs);
case 'sub_do':adm();
 foreach($subs as $i=>&$s)if($s['id']==$p['id']){if($p['do']=='del')array_splice($subs,$i,1);else $s['ok']=($p['do']=='ok');break;}
 wr('subs.json',array_values($subs));out(['ok'=>1]);
case 'save_settings':adm();
 foreach(['title','hero','tag'] as $k)if(isset($p[$k]))$S[$k]=$p[$k];
 if(!empty($p['newpw'])&&strlen($p['newpw'])>=6)$S['pw']=password_hash($p['newpw'],PASSWORD_DEFAULT);
 wr('settings.json',$S);out(['ok'=>1]);
case 'save_district':adm();
 $g=gd();$n=$p['name'];$old=$g[$n]??[];
 $g[$n]=['desc'=>$p['desc'],'how'=>$p['how'],'places'=>$p['places'],'time'=>$p['time'],'cost'=>$p['cost'],'img'=>(up('img')[0]??($old['img']??''))];
 wr('districts.json',$g);out(['ok'=>1]);
case 'upload_geo':adm();
 $j=@file_get_contents($_FILES['geo']['tmp_name']??'');if(!$j||!json_decode($j,true)['features'])out(['err'=>'ভুল GeoJSON']);
 file_put_contents($D.'geo.json',$j);out(['ok'=>1]);
}
out(['err'=>'?']);
