<?php
/**
 * lead_generator_api.php
 * Providers: TomTom (free, no card, no domain restriction) + Foursquare + Google
 */
require_once 'config.php';
requireLogin();
$db = getCRMDB(); $user = currentUser(); $uid = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function lgTable(mysqli $db):bool { return @$db->query("SELECT 1 FROM lead_gen_settings LIMIT 1") !== false; }
function lgGet(mysqli $db, string $k, string $def=''):string {
    $r = @$db->query("SELECT setting_val FROM lead_gen_settings WHERE setting_key='".$db->real_escape_string($k)."' LIMIT 1");
    if(!$r) return $def; $row=$r->fetch_assoc(); return $row ? trim((string)($row['setting_val']??$def)) : $def;
}
function lgUsed(mysqli $db, int $uid):int {
    $r=@$db->query("SELECT COALESCE(SUM(result_count),0) c FROM lead_gen_usage WHERE user_id=$uid AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
    return $r?(int)($r->fetch_assoc()['c']??0):0;
}
function lgHttp(string $url, array $headers=[]):?string {
    if(function_exists('curl_init')) {
        $ch=curl_init();
        curl_setopt_array($ch,[CURLOPT_URL=>$url,CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>false,
            CURLOPT_HTTPHEADER=>$headers,CURLOPT_USERAGENT=>'Mozilla/5.0 PadakCRM/1.0',
            CURLOPT_FOLLOWLOCATION=>true]);
        $r=curl_exec($ch); curl_close($ch); return $r?:null;
    }
    $o=['http'=>['timeout'=>20,'ignore_errors'=>true,'header'=>implode("\r\n",$headers)],'ssl'=>['verify_peer'=>false]];
    $r=@file_get_contents($url,false,stream_context_create($o));
    return $r!==false?$r:null;
}

if(!lgTable($db)){echo json_encode(['ok'=>false,'error'=>'Run migration_v11.sql in phpMyAdmin first.']);exit;}

// ══════════════════════════════════════════════════════════════
// TOMTOM SEARCH — FREE, no credit card, no domain restriction
// 2,500 calls/day free. signup: developer.tomtom.com
// Returns name+phone+address in ONE call (no second request needed)
// ══════════════════════════════════════════════════════════════
function searchTomTom(string $key, string $location, string $industry, int $count): array {
    $query = urlencode($industry);
    $loc   = urlencode($location);
    $url   = "https://api.tomtom.com/search/2/poiSearch/{$query}.json"
           . "?key={$key}&limit={$count}&countrySet=LK,IN,SG,MY,PH,BD,PK,NP&typeaheadSearch=false"
           . "&view=Unified&relatedPois=off";

    // Add near/location context
    // First geocode the city to get lat/lon for better results
    $geo_url = "https://api.tomtom.com/search/2/geocode/".urlencode($location).".json?key={$key}&limit=1";
    $geo_raw = lgHttp($geo_url);
    if ($geo_raw) {
        $geo = json_decode($geo_raw, true);
        if (!empty($geo['results'][0]['position'])) {
            $lat = $geo['results'][0]['position']['lat'];
            $lon = $geo['results'][0]['position']['lon'];
            $url .= "&lat={$lat}&lon={$lon}&radius=50000";
        }
    }

    $raw = lgHttp($url);
    if (!$raw) return ['error'=>'Cannot reach TomTom API. Check server internet connection.'];

    $data = json_decode($raw, true);

    if (isset($data['httpStatusCode']) && $data['httpStatusCode'] !== 200) {
        if ($data['httpStatusCode'] == 403) return ['error'=>'Invalid TomTom API key. Check your key in Settings.'];
        return ['error'=>'TomTom error '.$data['httpStatusCode'].': '.($data['detailedError']['message']??'Unknown')];
    }
    if (!isset($data['results'])) return ['error'=>'TomTom unexpected response: '.substr($raw,0,200)];
    if (empty($data['results'])) return ['leads'=>[]];

    $leads = [];
    foreach ($data['results'] as $r) {
        $poi     = $r['poi']     ?? [];
        $addr    = $r['address'] ?? [];
        $name    = $poi['name']  ?? '';
        if (!$name) continue;

        $phone   = $poi['phone']                     ?? '';
        $website = $poi['url']                       ?? '';
        $address = $addr['freeformAddress']          ?? '';
        if ($address && !empty($addr['country'])) $address .= ', '.$addr['country'];

        $leads[] = ['place_id'=>'tt_'.md5($name.$address), 'name'=>$name,
                    'phone'=>$phone, 'address'=>$address, 'website'=>$website, 'rating'=>null];
    }
    return ['leads'=>$leads];
}

// ══════════════════════════════════════════════════════════════
// FOURSQUARE — free, but needs Allowed Hosts set to * in dashboard
// Fix: developer.foursquare.com → Project → Settings → Allowed Hosts → add *
// ══════════════════════════════════════════════════════════════
function searchFoursquare(string $key, string $location, string $industry, int $count): array {
    $geo_raw = lgHttp('https://nominatim.openstreetmap.org/search?format=json&limit=1&q='.urlencode($location),
        ['User-Agent: PadakCRM/1.0','Accept-Language: en']);
    $lat=$lng='';
    if($geo_raw){$g=json_decode($geo_raw,true);if(!empty($g[0]['lat'])){$lat=$g[0]['lat'];$lng=$g[0]['lon'];}}

    $params=['query'=>$industry,'limit'=>min($count,50),'fields'=>'fsq_id,name,location,tel,website,rating'];
    if($lat&&$lng){$params['ll']="$lat,$lng";$params['radius']=50000;}else{$params['near']=$location;}

    $url='https://api.foursquare.com/v3/places/search?'.http_build_query($params);
    $raw=lgHttp($url,["Authorization: $key","Accept: application/json"]);
    if(!$raw)return['error'=>'Cannot reach Foursquare API.'];
    $data=json_decode($raw,true);

    if(!isset($data['results'])){
        $msg=substr($raw,0,300);
        if(strpos($raw,'not in allowlist')!==false||strpos($raw,'allowlist')!==false)
            return['error'=>"ALLOWLIST ERROR: Your server's IP/domain is not whitelisted in Foursquare.\n\nFix in 10 seconds:\n1. Go to developer.foursquare.com → Your Project → Settings\n2. Find 'Allowed Hosts' section\n3. Add: * (just an asterisk)\n4. Save. Done."];
        if(strpos($raw,'Unauthorized')!==false||strpos($raw,'Invalid')!==false||strpos($raw,'token')!==false)
            return['error'=>"Invalid Foursquare key. Use the SERVICE API KEY (not Client ID/Secret).\nIn Foursquare: Project → Settings → Service API Key → copy that key."];
        return['error'=>'Foursquare: '.$msg];
    }
    if(empty($data['results']))return['leads'=>[]];

    $leads=[];
    foreach($data['results'] as $p){
        $pid=$p['fsq_id']??''; $name=$p['name']??''; if(!$name)continue;
        $loc=$p['location']??[]; $parts=array_filter([$loc['address']??'',$loc['locality']??'',$loc['region']??'',$loc['country']??'']);
        $phone=$p['tel']??''; $website=$p['website']??''; $rt=isset($p['rating'])?round($p['rating']/2,1):null;
        $leads[]=['place_id'=>$pid,'name'=>$name,'phone'=>$phone,'address'=>implode(', ',$parts),'website'=>$website,'rating'=>$rt];
    }
    return['leads'=>$leads];
}

// ══════════════════════════════════════════════════════════════
// GOOGLE PLACES
// ══════════════════════════════════════════════════════════════
function searchGoogle(string $key, string $location, string $industry, int $count): array {
    $url='https://maps.googleapis.com/maps/api/place/textsearch/json?query='.urlencode("$industry in $location")."&key=$key";
    $raw=lgHttp($url); if(!$raw)return['error'=>'Cannot reach Google API.'];
    $data=json_decode($raw,true);
    if(!isset($data['results']))return['error'=>'Google: '.($data['status']??'error').'. '.($data['error_message']??'')];
    if($data['status']==='ZERO_RESULTS')return['leads'=>[]];
    $leads=[];
    foreach(array_slice($data['results'],0,$count) as $p){
        $pid=$p['place_id']??''; $phone=''; $website='';
        if($pid){$dr=lgHttp("https://maps.googleapis.com/maps/api/place/details/json?place_id=$pid&fields=formatted_phone_number,website&key=$key");
            if($dr){$d=json_decode($dr,true);$phone=$d['result']['formatted_phone_number']??'';$website=$d['result']['website']??'';}}
        $leads[]=['place_id'=>$pid,'name'=>$p['name']??'','phone'=>$phone,'address'=>$p['formatted_address']??'','website'=>$website,'rating'=>isset($p['rating'])?(float)$p['rating']:null];
    }
    return['leads'=>$leads];
}

// ── SAVE TO DB + BUILD RESPONSE ──
function lgSaveResults(mysqli $db, int $uid, array $raw_leads, string $location, string $industry): array {
    $out=[];
    foreach($raw_leads as $l){
        $pe=$db->real_escape_string($l['place_id']??''); $ne=$db->real_escape_string($l['name']??'');
        $phe=$db->real_escape_string($l['phone']??''); $ae=$db->real_escape_string($l['address']??'');
        $we=$db->real_escape_string($l['website']??''); $le=$db->real_escape_string($location); $ie=$db->real_escape_string($industry);
        $rt=isset($l['rating'])?(float)$l['rating']:null; $rts=$rt!==null?$rt:'NULL';
        $db->query("INSERT INTO lead_gen_results (user_id,place_id,name,phone,address,website,rating,location,industry,imported) VALUES ($uid,'$pe','$ne','$phe','$ae','$we',$rts,'$le','$ie',0)");
        $rid=(int)$db->insert_id;
        $out[]=['id'=>$rid,'place_id'=>$l['place_id']??'','name'=>$l['name']??'','phone'=>$l['phone']??'','address'=>$l['address']??'','website'=>$l['website']??'','rating'=>$rt,'imported'=>false];
    }
    return $out;
}

// ══════════════════════════════════════════════════════════════
// ACTION: SEARCH
// ══════════════════════════════════════════════════════════════
if($action==='search'){
    $location=trim($_POST['location']??''); $industry=trim($_POST['industry']??'');
    $count=min(20,max(1,(int)($_POST['count']??5)));
    if(!$location||!$industry){echo json_encode(['ok'=>false,'error'=>'Location and industry are required']);exit;}

    $provider=lgGet($db,'api_provider','tomtom');
    $quota=(int)lgGet($db,'monthly_quota','2500');
    $used=lgUsed($db,$uid);
    if($used+$count>$quota){echo json_encode(['ok'=>false,'error'=>"Quota ($quota/month) reached. Used: $used."]);exit;}

    switch($provider){
        case 'tomtom':
            $k=lgGet($db,'tomtom_key');
            if(!$k){echo json_encode(['ok'=>false,'error'=>'TomTom API key not set. Open ⚙ Settings.']);exit;}
            $result=searchTomTom($k,$location,$industry,$count); break;
        case 'foursquare':
            $k=lgGet($db,'foursquare_key');
            if(!$k){echo json_encode(['ok'=>false,'error'=>'Foursquare API key not set. Open ⚙ Settings.']);exit;}
            $result=searchFoursquare($k,$location,$industry,$count); break;
        default:
            $k=lgGet($db,'google_api_key');
            if(!$k){echo json_encode(['ok'=>false,'error'=>'Google API key not set. Open ⚙ Settings.']);exit;}
            $result=searchGoogle($k,$location,$industry,$count); break;
    }

    if(isset($result['error'])){echo json_encode(['ok'=>false,'error'=>$result['error']]);exit;}
    $raw=$result['leads']??[];
    if(empty($raw)){echo json_encode(['ok'=>true,'leads'=>[],'message'=>"No results for '$industry' in '$location'. Try broader terms."]);exit;}

    $out=lgSaveResults($db,$uid,$raw,$location,$industry);
    $rc=count($out);
    $db->query("INSERT INTO lead_gen_usage (user_id,location,industry,result_count) VALUES ($uid,'".$db->real_escape_string($location)."','".$db->real_escape_string($industry)."',$rc)");
    logActivity('generated leads',"$industry in $location",0,"$rc leads via $provider");
    echo json_encode(['ok'=>true,'leads'=>$out,'used'=>$used+$rc,'quota'=>$quota,'provider'=>$provider]);
    exit;
}

// ── TEST KEY ──
if($action==='test_key'&&isAdmin()){
    // Use provider from POST if sent (tests what's shown in UI), else fall back to DB
    $provider = trim($_POST['provider'] ?? lgGet($db,'api_provider','tomtom'));
    if(!in_array($provider,['tomtom','foursquare','google'])) $provider='tomtom';
    // Key can come from POST (just typed, not yet saved) or from DB (already saved)
    $posted_key = trim($_POST['api_key'] ?? '');
    $db_key = lgGet($db,$provider==='tomtom'?'tomtom_key':($provider==='foursquare'?'foursquare_key':'google_api_key'));
    $k = $posted_key ?: $db_key;
    if(!$k){echo json_encode(['ok'=>false,'error'=>'No API key saved yet.']);exit;}

    if($provider==='tomtom'){
        $url="https://api.tomtom.com/search/2/poiSearch/restaurant.json?key={$k}&limit=1&countrySet=LK";
        $raw=lgHttp($url); $data=json_decode($raw??'{}',true);
        if(!empty($data['results']))echo json_encode(['ok'=>true,'message'=>'✅ TomTom API key works! Found: '.$data['results'][0]['poi']['name'].' — Lead Generator ready.']);
        elseif(isset($data['httpStatusCode'])&&$data['httpStatusCode']==403)echo json_encode(['ok'=>false,'error'=>'Invalid TomTom key. Double-check you copied it correctly.']);
        else echo json_encode(['ok'=>false,'error'=>'TomTom response: '.substr($raw??'no response',0,300)]);
    } elseif($provider==='foursquare'){
        $raw=lgHttp('https://api.foursquare.com/v3/places/search?query=coffee&near=London&limit=1',["Authorization: $k","Accept: application/json"]);
        $data=json_decode($raw??'{}',true);
        if(!empty($data['results']))echo json_encode(['ok'=>true,'message'=>'✅ Foursquare key works!']);
        elseif(strpos($raw??'','allowlist')!==false)echo json_encode(['ok'=>false,'error'=>"Add * to Foursquare Allowed Hosts:\nProject → Settings → Allowed Hosts → add * → Save"]);
        else echo json_encode(['ok'=>false,'error'=>'Foursquare: '.substr($raw??'no response',0,300)]);
    } else {
        $raw=lgHttp("https://maps.googleapis.com/maps/api/place/textsearch/json?query=coffee&key=$k");
        $data=json_decode($raw??'{}',true);
        if(!empty($data['results']))echo json_encode(['ok'=>true,'message'=>'✅ Google API key works!']);
        else echo json_encode(['ok'=>false,'error'=>'Google: '.($data['status']??'error')]);
    }
    exit;
}

// ── IMPORT LEAD ──
if($action==='import_lead'){
    $rid=(int)($_POST['result_id']??0);
    $row=@$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid")->fetch_assoc();
    if(!$row){echo json_encode(['ok'=>false,'error'=>'Not found']);exit;}
    if($row['imported']){echo json_encode(['ok'=>false,'error'=>'Already imported']);exit;}
    $n=$row['name'];$p=$row['phone'];$svc=$row['industry'];
    $notes="Lead Generator: {$row['industry']} in {$row['location']}.";
    if($row['website'])$notes.=" Web: {$row['website']}";
    if($row['rating'])$notes.=" Rating: {$row['rating']}/5";
    $e='';$null=null;$bc='LKR';$st='new';$pr='medium';$src='other';
    $stmt=$db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssdsssssssii",$n,$n,$e,$p,$src,$svc,$null,$bc,$st,$pr,$null,$null,$notes,$null,$null,$uid);
    $stmt->execute();$lid=(int)$db->insert_id;
    $db->query("UPDATE lead_gen_results SET imported=1,lead_id=$lid WHERE id=$rid");
    logActivity('imported lead',$n,$lid);
    echo json_encode(['ok'=>true,'lead_id'=>$lid,'message'=>"$n imported to CRM Leads"]);exit;
}

// ── IMPORT ALL ──
if($action==='import_all'){
    $ids=array_filter(array_map('intval',explode(',',$_POST['ids']??'')));
    $imported=0;
    foreach($ids as $rid){
        $row=@$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid AND imported=0")->fetch_assoc();
        if(!$row)continue;
        $n=$row['name'];$p=$row['phone'];$svc=$row['industry'];
        $notes="Lead Generator: {$row['industry']} in {$row['location']}.";
        if($row['website'])$notes.=" Web: {$row['website']}";
        $e='';$null=null;$bc='LKR';$st='new';$pr='medium';$src='other';
        $stmt=$db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssdsssssssii",$n,$n,$e,$p,$src,$svc,$null,$bc,$st,$pr,$null,$null,$notes,$null,$null,$uid);
        $stmt->execute();$lid=(int)$db->insert_id;
        $db->query("UPDATE lead_gen_results SET imported=1,lead_id=$lid WHERE id=$rid");
        $imported++;
    }
    logActivity('bulk imported leads','',0,"$imported leads");
    echo json_encode(['ok'=>true,'imported'=>$imported]);exit;
}

// ── GET STATS ──
if($action==='get_stats'){
    $quota=(int)lgGet($db,'monthly_quota','2500');$used=lgUsed($db,$uid);
    $provider=lgGet($db,'api_provider','tomtom');
    $km=['tomtom'=>'tomtom_key','foursquare'=>'foursquare_key','google'=>'google_api_key'];
    $api_set=lgGet($db,$km[$provider]??'tomtom_key')!=='';
    $trend=[];$tr=@$db->query("SELECT DATE_FORMAT(created_at,'%b') mo,DATE_FORMAT(created_at,'%Y-%m') smo,SUM(result_count) cnt FROM lead_gen_usage WHERE user_id=$uid AND created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY mo,smo ORDER BY smo");
    if($tr)$trend=$tr->fetch_all(MYSQLI_ASSOC);
    $recent=[];$rc=@$db->query("SELECT industry,location,result_count,created_at FROM lead_gen_usage WHERE user_id=$uid ORDER BY created_at DESC LIMIT 8");
    if($rc)$recent=$rc->fetch_all(MYSQLI_ASSOC);
    $total_imp=(int)(@$db->query("SELECT COUNT(*) c FROM lead_gen_results WHERE user_id=$uid AND imported=1")->fetch_assoc()['c']??0);
    echo json_encode(['ok'=>true,'used'=>$used,'quota'=>$quota,'api_set'=>$api_set,'provider'=>$provider,'trend'=>$trend,'recent'=>$recent,'total_imp'=>$total_imp]);exit;
}

// ── SAVE SETTINGS ──
if($action==='save_settings'&&isAdmin()){
    $prov=$_POST['provider']??'tomtom'; if(!in_array($prov,['tomtom','foursquare','google']))$prov='tomtom';
    $tk=trim($_POST['tomtom_key']??''); $fk=trim($_POST['foursquare_key']??''); $gk=trim($_POST['google_key']??'');
    $quota=max(10,(int)($_POST['quota']??2500));
    $db->query("UPDATE lead_gen_settings SET setting_val='$prov',updated_by=$uid WHERE setting_key='api_provider'");
    if($tk)$db->query("UPDATE lead_gen_settings SET setting_val='".$db->real_escape_string($tk)."',updated_by=$uid WHERE setting_key='tomtom_key'");
    if($fk)$db->query("UPDATE lead_gen_settings SET setting_val='".$db->real_escape_string($fk)."',updated_by=$uid WHERE setting_key='foursquare_key'");
    if($gk)$db->query("UPDATE lead_gen_settings SET setting_val='".$db->real_escape_string($gk)."',updated_by=$uid WHERE setting_key='google_api_key'");
    $db->query("UPDATE lead_gen_settings SET setting_val=$quota,updated_by=$uid WHERE setting_key='monthly_quota'");
    echo json_encode(['ok'=>true,'message'=>'Settings saved']);exit;
}

// ── EXPORT CSV ──
if($action==='export_excel'){
    $ids=array_filter(array_map('intval',explode(',',$_POST['ids']??'')));
    if(!$ids){echo json_encode(['ok'=>false,'error'=>'No data']);exit;}
    $rows=@$db->query("SELECT name,phone,address,website,rating,industry,location FROM lead_gen_results WHERE id IN(".implode(',',$ids).") AND user_id=$uid")->fetch_all(MYSQLI_ASSOC);
    $csv="Name,Phone,Address,Website,Rating,Industry,Location\n";
    foreach($rows as $r)$csv.=implode(',',array_map(fn($v)=>'"'.str_replace('"','""',(string)($v??'')).'"',array_values($r)))."\n";
    echo json_encode(['ok'=>true,'csv'=>$csv]);exit;
}

echo json_encode(['ok'=>false,'error'=>'Unknown action']);