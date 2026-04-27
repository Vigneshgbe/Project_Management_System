<?php
/**
 * lead_generator_api.php — Google Places API
 * With per-role / per-user quota allocation system
 */
require_once 'config.php';
requireLogin();
$db   = getCRMDB();
$user = currentUser();
$uid  = (int)$user['id'];
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function lgGet(mysqli $db, string $k, string $def = ''): string {
    $r = @$db->query("SELECT setting_val FROM lead_gen_settings WHERE setting_key='".$db->real_escape_string($k)."' LIMIT 1");
    if (!$r) return $def;
    $row = $r->fetch_assoc();
    return $row ? trim((string)($row['setting_val'] ?? $def)) : $def;
}
function lgSet(mysqli $db, string $k, string $v, int $uid): void {
    $ke = $db->real_escape_string($k);
    $ve = $db->real_escape_string($v);
    $db->query("INSERT INTO lead_gen_settings (setting_key,setting_val,updated_by) VALUES ('$ke','$ve',$uid) ON DUPLICATE KEY UPDATE setting_val='$ve',updated_by=$uid");
}
function lgTableOk(mysqli $db): bool {
    return @$db->query("SELECT 1 FROM lead_gen_settings LIMIT 1") !== false;
}
function lgMonthUsed(mysqli $db, int $uid): array {
    $r = @$db->query("SELECT COALESCE(SUM(result_count),0) leads, COALESCE(SUM(api_calls_used),0) calls, COALESCE(SUM(estimated_cost),0) cost FROM lead_gen_usage WHERE user_id=$uid AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
    return $r ? ($r->fetch_assoc() ?: ['leads'=>0,'calls'=>0,'cost'=>0]) : ['leads'=>0,'calls'=>0,'cost'=>0];
}
function lgHttp(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch,[CURLOPT_URL=>$url,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_USERAGENT=>'Mozilla/5.0 (compatible; PadakCRM/1.0)',CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>3]);
        $r=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
        return ($code>=200&&$code<400&&$r)?$r:null;
    }
    $ctx=stream_context_create(['http'=>['timeout'=>15,'ignore_errors'=>true,'user_agent'=>'Mozilla/5.0'],'ssl'=>['verify_peer'=>false]]);
    $r=@file_get_contents($url,false,$ctx); return $r!==false?$r:null;
}
function lgScrapeEmail(string $url): string {
    if (!$url) return '';
    $base=rtrim(preg_replace('#^(https?://[^/]+).*#','$1',$url),'/');
    $pages=[$url,$base.'/contact',$base.'/contact-us'];
    foreach ($pages as $page) {
        $html=lgHttp($page); if (!$html) continue;
        if (preg_match_all('/mailto:([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,6})/i',$html,$m))
            foreach ($m[1] as $c) { $e=strtolower(trim($c)); if (!preg_match('/(example|noreply|no-reply|sentry|w3\.org|schema|privacy|legal|unsubscribe)/i',$e)) return $e; }
        if (preg_match_all('/\b([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,6})\b/',$html,$m))
            foreach ($m[1] as $c) { $e=strtolower(trim($c)); if (!preg_match('/(example|noreply|no-reply|sentry|w3\.org|schema|privacy|legal|unsubscribe|\.png|\.jpg|\.gif)/i',$e)) return $e; }
    }
    return '';
}
function lgIsRealWebsite(string $url): bool {
    if (!$url) return false;
    $bad=['facebook.com','fb.com','instagram.com','twitter.com','x.com','g.co','goo.gl','maps.google.com','google.com/maps','yelp.com','tripadvisor.com','justdial.com','indiamart.com','youtube.com','linkedin.com','whatsapp.com','t.me'];
    $low=strtolower($url); foreach ($bad as $b) { if (strpos($low,$b)!==false) return false; } return true;
}
/**
 * lgFindWebsite — Deep-searches for a business website when Google Places returns none.
 * Tries slug-based domain guesses + a Google search fallback.
 * Returns ['website'=>string, 'email'=>string, 'phone'=>string]
 * Only called when has_website===0. Adds ~0.5s latency per lead, no extra API cost.
 */
function lgFindWebsite(string $name, string $location): array {
    $empty = ['website'=>'','email'=>'','phone'=>''];

    // ── Step 1: Build slug variants from business name ──────────────────────
    $slug = strtolower(trim($name));
    $slug = preg_replace('/\b(pvt|ltd|llc|inc|corp|company|co|the|&|and|sdn|bhd|fze|fzc|llp|plc)\b/i', '', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '', $slug);   // remove all non-alphanumeric
    if (strlen($slug) < 3) return $empty;

    // Build country-aware TLD list from location string
    $loc_lower = strtolower($location);
    $tlds = ['.com'];
    if (strpos($loc_lower,'india')!==false||strpos($loc_lower,' in')!==false)  $tlds = ['.com','.in','.co.in','.net','.org'];
    elseif (strpos($loc_lower,'sri lanka')!==false||strpos($loc_lower,'lk')!==false) $tlds = ['.com','.lk','.net'];
    elseif (strpos($loc_lower,'uae')!==false||strpos($loc_lower,'dubai')!==false||strpos($loc_lower,'abu dhabi')!==false) $tlds = ['.com','.ae','.net'];
    elseif (strpos($loc_lower,'uk')!==false||strpos($loc_lower,'england')!==false||strpos($loc_lower,'london')!==false) $tlds = ['.com','.co.uk','.net'];
    elseif (strpos($loc_lower,'australia')!==false||strpos($loc_lower,'sydney')!==false) $tlds = ['.com','.com.au','.net.au'];
    elseif (strpos($loc_lower,'malaysia')!==false||strpos($loc_lower,'singapore')!==false) $tlds = ['.com','.com.my','.com.sg'];
    elseif (strpos($loc_lower,'canada')!==false) $tlds = ['.com','.ca','.net'];
    else $tlds = ['.com','.net','.org','.co'];

    $candidates = [];
    foreach ($tlds as $tld) {
        $candidates[] = 'https://www.'.$slug.$tld;
        $candidates[] = 'https://'.$slug.$tld;
    }

    // ── Step 2: Probe each candidate URL ────────────────────────────────────
    foreach ($candidates as $url) {
        $html = lgHttpHead($url);   // cheap HEAD check first
        if ($html === null) continue;
        // Confirm it's a real page (not 404/redirect to unrelated domain)
        $resolved = lgGetFinalUrl($url);
        if (!$resolved) continue;
        // Make sure the domain still contains the slug (avoid 301→parked pages)
        $domain_slug = preg_replace('/[^a-z0-9]/', '', strtolower(parse_url($resolved, PHP_URL_HOST) ?? ''));
        if (similar_text($slug, $domain_slug) < max(3, strlen($slug)*0.5)) continue;
        // Found a live site — now scrape contact details
        $email = lgScrapeEmail($resolved);
        $phone = lgScrapePhone($resolved);
        return ['website'=>$resolved, 'email'=>$email, 'phone'=>$phone];
    }

    // ── Step 3: Google search fallback (no API key needed) ──────────────────
    $query = urlencode('"'.$name.'" '.$location.' official website');
    $search_url = 'https://www.google.com/search?q='.$query.'&num=5';
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept-Language: en-US,en;q=0.9',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    ];
    $html = lgHttpWithHeaders($search_url, $headers);
    if (!$html) return $empty;

    // Extract URLs from Google results — look for cite tags and href patterns
    preg_match_all('/<cite[^>]*>(https?:\/\/[^<\s]+)<\/cite>/i', $html, $m1);
    preg_match_all('/href="(https?:\/\/(?!(?:www\.google|accounts\.google|support\.google|maps\.google|webcache\.googleusercontent))[^"&\s]{10,})"/', $html, $m2);
    $found_urls = array_unique(array_merge($m1[1] ?? [], $m2[1] ?? []));

    foreach (array_slice($found_urls, 0, 5) as $candidate) {
        if (!lgIsRealWebsite($candidate)) continue;
        // Check domain contains slug characters
        $domain_slug = preg_replace('/[^a-z0-9]/', '', strtolower(parse_url($candidate, PHP_URL_HOST) ?? ''));
        $name_slug = preg_replace('/[^a-z0-9]/', '', strtolower($name));
        // Use a relaxed match — at least 40% similarity or slug is substring
        if (strpos($domain_slug, substr($slug, 0, 4)) === false && similar_text($slug, $domain_slug) < strlen($slug) * 0.4) continue;
        $email = lgScrapeEmail($candidate);
        $phone = lgScrapePhone($candidate);
        return ['website'=>$candidate, 'email'=>$email, 'phone'=>$phone];
    }

    return $empty;
}

/** HEAD request — returns empty string on 200-399, null on failure/404 */
function lgHttpHead(string $url): ?string {
    if (!function_exists('curl_init')) {
        $ctx = stream_context_create(['http'=>['method'=>'HEAD','timeout'=>6,'ignore_errors'=>true],'ssl'=>['verify_peer'=>false]]);
        $r = @file_get_contents($url, false, $ctx);
        // Check $http_response_header
        if (!empty($http_response_header)) {
            $code = (int)preg_replace('/.*?(\d{3}).*/', '$1', $http_response_header[0] ?? '');
            return ($code >= 200 && $code < 400) ? '' : null;
        }
        return null;
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_NOBODY         => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; PadakCRM/1.0)',
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code >= 200 && $code < 400) ? '' : null;
}

/** GET request and return final resolved URL after redirects */
function lgGetFinalUrl(string $url): ?string {
    if (!function_exists('curl_init')) return $url;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; PadakCRM/1.0)',
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ($code >= 200 && $code < 400 && $body) ? ($final ?: $url) : null;
}

/** HTTP GET with custom headers (used for Google search) */
function lgHttpWithHeaders(string $url, array $headers): ?string {
    if (!function_exists('curl_init')) return lgHttp($url);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $r = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code >= 200 && $code < 400 && $r) ? $r : null;
}

/** Scrape phone number from a webpage (contact/about pages) */
function lgScrapePhone(string $url): string {
    if (!$url) return '';
    $base = rtrim(preg_replace('#^(https?://[^/]+).*#', '$1', $url), '/');
    $pages = [$url, $base.'/contact', $base.'/contact-us', $base.'/about', $base.'/about-us'];
    foreach ($pages as $page) {
        $html = lgHttp($page); if (!$html) continue;
        // International format: +971 50 123 4567 / +91-98765-43210
        if (preg_match_all('/\+[\d\s\-().]{7,18}\d/', $html, $m))
            foreach ($m[0] as $p) { $p = trim(preg_replace('/\s+/', ' ', $p)); if (strlen($p) >= 9) return $p; }
        // Local formats: 050 123 4567 / (044) 234-5678
        if (preg_match_all('/(?:\(?\d{2,5}\)?[\s\-.]?\d{2,5}[\s\-.]?\d{3,6})/', $html, $m))
            foreach ($m[0] as $p) { $p = preg_replace('/[^\d\s\-+().]/', '', $p); $digits = preg_replace('/\D/', '', $p); if (strlen($digits) >= 8 && strlen($digits) <= 15) return trim($p); }
    }
    return '';
}
function lgEnsureColumns(mysqli $db): void {
    static $done=false; if ($done) return; $done=true;
    $cols=[]; $r=@$db->query("SHOW COLUMNS FROM lead_gen_results"); if ($r) while ($row=$r->fetch_assoc()) $cols[]=$row['Field'];
    $add=[];
    if (!in_array('owner_name',$cols))        $add[]="ADD COLUMN owner_name        VARCHAR(200) DEFAULT NULL AFTER name";
    if (!in_array('email',$cols))             $add[]="ADD COLUMN email             VARCHAR(200) DEFAULT NULL AFTER phone";
    if (!in_array('has_website',$cols))       $add[]="ADD COLUMN has_website       TINYINT(1) DEFAULT 0 AFTER website";
    if (!in_array('ratings_total',$cols))     $add[]="ADD COLUMN ratings_total     INT DEFAULT 0 AFTER rating";
    if (!in_array('price_level',$cols))       $add[]="ADD COLUMN price_level       TINYINT DEFAULT NULL AFTER ratings_total";
    if (!in_array('opportunity_score',$cols)) $add[]="ADD COLUMN opportunity_score INT DEFAULT 0 AFTER price_level";
    if (!in_array('api_calls',$cols))         $add[]="ADD COLUMN api_calls         INT DEFAULT 0";
    if (!in_array('website_found_by_crawler',$cols)) $add[]="ADD COLUMN website_found_by_crawler TINYINT(1) DEFAULT 0 COMMENT 'Set to 1 when website was found by deep-search, not Google Places'";
    if ($add) @$db->query("ALTER TABLE lead_gen_results ".implode(', ',$add));
    @$db->query("UPDATE lead_gen_results SET has_website=0 WHERE (website IS NULL OR website='')");
    @$db->query("UPDATE lead_gen_results SET has_website=0 WHERE website IS NOT NULL AND website!='' AND (website LIKE '%facebook.com%' OR website LIKE '%fb.com%' OR website LIKE '%instagram.com%' OR website LIKE '%twitter.com%' OR website LIKE '%x.com%' OR website LIKE '%g.co%' OR website LIKE '%goo.gl%' OR website LIKE '%maps.google%' OR website LIKE '%youtube.com%' OR website LIKE '%linkedin.com%' OR website LIKE '%whatsapp.com%' OR website LIKE '%t.me%' OR website LIKE '%justdial.com%' OR website LIKE '%indiamart.com%' OR website LIKE '%tripadvisor.com%' OR website LIKE '%yelp.com%')");
    @$db->query("UPDATE lead_gen_results SET has_website=1 WHERE website IS NOT NULL AND website!='' AND has_website=0 AND website NOT LIKE '%facebook.com%' AND website NOT LIKE '%fb.com%' AND website NOT LIKE '%instagram.com%' AND website NOT LIKE '%twitter.com%' AND website NOT LIKE '%x.com%' AND website NOT LIKE '%g.co%' AND website NOT LIKE '%goo.gl%' AND website NOT LIKE '%maps.google%' AND website NOT LIKE '%youtube.com%' AND website NOT LIKE '%linkedin.com%' AND website NOT LIKE '%whatsapp.com%' AND website NOT LIKE '%t.me%' AND website NOT LIKE '%justdial.com%' AND website NOT LIKE '%indiamart.com%' AND website NOT LIKE '%tripadvisor.com%' AND website NOT LIKE '%yelp.com%'");
    @$db->query("UPDATE lead_gen_results SET opportunity_score = CASE WHEN has_website=0 THEN 50 ELSE 0 END + CASE WHEN ratings_total>=100 THEN 20 WHEN ratings_total>=30 THEN 12 WHEN ratings_total>=10 THEN 6 ELSE 0 END + CASE WHEN price_level>=3 THEN 15 WHEN price_level=2 THEN 8 ELSE 0 END + CASE WHEN rating>=4.0 THEN 10 ELSE 0 END + CASE WHEN phone IS NOT NULL AND phone!='' THEN 5 ELSE 0 END WHERE opportunity_score=0");
}
function lgGetQuotaConfig(mysqli $db): array {
    $roles=json_decode(lgGet($db,'quota_roles','{}'),true)?:[];
    $users=json_decode(lgGet($db,'quota_users','{}'),true)?:[];
    if (!isset($roles['admin']))   $roles['admin']=300;
    if (!isset($roles['manager'])) $roles['manager']=100;
    if (!isset($roles['user']))    $roles['user']=30;
    return ['roles'=>$roles,'users'=>$users];
}
function lgUserQuota(mysqli $db, array $user, int $globalQuota): array {
    if (function_exists('isAdmin')&&isAdmin()) return ['quota'=>$globalQuota,'source'=>'global','blocked'=>false];
    $cfg=lgGetQuotaConfig($db); $uid_str=(string)$user['id'];
    if (array_key_exists($uid_str,$cfg['users'])) {
        $v=(int)$cfg['users'][$uid_str];
        if ($v===0)  return ['quota'=>0,'source'=>'user','blocked'=>true];
        if ($v!==-1) return ['quota'=>$v,'source'=>'user','blocked'=>false];
    }
    $role=strtolower($user['role']??'user');
    if (function_exists('isManager')&&isManager()&&$role!=='admin') $role='manager';
    $q=(int)($cfg['roles'][$role]??$cfg['roles']['user']??30);
    return ['quota'=>max(0,$q),'source'=>'role','blocked'=>($q===0)];
}

if (!lgTableOk($db)) { echo json_encode(['ok'=>false,'error'=>'Run migration_v11.sql and migration_v14.sql first.']); exit; }
lgEnsureColumns($db);

// ── SEARCH ────────────────────────────────────────────────────────────────────
if ($action==='search') {
    $location=trim($_POST['location']??''); $industry=trim($_POST['industry']??'');
    $count=min(20,max(1,(int)($_POST['count']??5)));
    $search_mode=in_array($_POST['search_mode']??'',['no_website','high_value','all'])?$_POST['search_mode']:'all';
    if (!$location||!$industry) { echo json_encode(['ok'=>false,'error'=>'Location and industry are required']); exit; }
    $api_key=lgGet($db,'google_api_key');
    if (!$api_key) { echo json_encode(['ok'=>false,'error'=>'Google API key not configured. Click Settings.']); exit; }
    $globalQuota=max(1,(int)lgGet($db,'monthly_quota','300'));
    $budget_usd=(float)lgGet($db,'monthly_budget_usd','15.00');
    $cost_ts=(float)lgGet($db,'cost_per_textsearch','0.032');
    $cost_det=(float)lgGet($db,'cost_per_details','0.003');
    $uqInfo=lgUserQuota($db,$user,$globalQuota);
    if ($uqInfo['blocked']) { echo json_encode(['ok'=>false,'error'=>'Your account is blocked from Lead Generator. Contact admin.']); exit; }
    $userQuota=$uqInfo['quota']; $usage=lgMonthUsed($db,$uid); $usedLeads=(int)$usage['leads'];
    if ($usedLeads+$count>$userQuota) { $rem=max(0,$userQuota-$usedLeads); echo json_encode(['ok'=>false,'error'=>"Your monthly lead quota is $userQuota ($usedLeads used, $rem remaining). Ask admin to increase your limit."]); exit; }
    $gr=@$db->query("SELECT COALESCE(SUM(estimated_cost),0) c FROM lead_gen_usage WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
    $globalUsedCost=$gr?(float)($gr->fetch_assoc()['c']??0):0;
    if ($globalUsedCost+($cost_ts+$cost_det*$count)>$budget_usd) { echo json_encode(['ok'=>false,'error'=>'System API budget reached for this month. Contact admin.']); exit; }
    $query=urlencode("$industry in $location");
    $search_url="https://maps.googleapis.com/maps/api/place/textsearch/json?query=$query&key=$api_key";
    $raw=lgHttp($search_url); $api_calls=1;
    if (!$raw) { echo json_encode(['ok'=>false,'error'=>'Cannot reach Google API.']); exit; }
    $data=json_decode($raw,true);
    if (!isset($data['status'])) { echo json_encode(['ok'=>false,'error'=>'Invalid Google API response.']); exit; }
    if ($data['status']==='REQUEST_DENIED') { $msg=$data['error_message']??''; echo json_encode(['ok'=>false,'error'=>strpos($msg,'Places API')!==false?"Places API not enabled.\nCloud Console => APIs & Services => Enable Places API.\n\n$msg":"API key error: $msg"]); exit; }
    if ($data['status']==='ZERO_RESULTS'||empty($data['results'])) { echo json_encode(['ok'=>true,'leads'=>[],'message'=>"No businesses found for '$industry' in '$location'."]); exit; }
    if (!in_array($data['status'],['OK','ZERO_RESULTS'])) { echo json_encode(['ok'=>false,'error'=>'Google API: '.$data['status'].' - '.($data['error_message']??'')]); exit; }
    $results=array_slice($data['results'],0,$count); $leads=[];
    foreach ($results as $place) {
        $place_id=$place['place_id']??''; $name=$place['name']??''; if (!$name) continue;
        $address=$place['formatted_address']??($place['vicinity']??'');
        $rating=isset($place['rating'])?(float)$place['rating']:null;
        $phone='';$website='';$email='';$owner_name='';$has_website=0;$ratings_total=0;$price_level=null;
        if ($place_id) {
            $fields='formatted_phone_number,international_phone_number,website,reviews,user_ratings_total,price_level,business_status';
            $det_raw=lgHttp("https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=$fields&key=$api_key");
            $api_calls++;
            if ($det_raw) {
                $det=json_decode($det_raw,true); $res=$det['result']??[];
                $phone=$res['formatted_phone_number']??($res['international_phone_number']??'');
                $website=$res['website']??''; $has_website=lgIsRealWebsite($website)?1:0;
                $ratings_total=(int)($res['user_ratings_total']??0);
                $price_level=isset($res['price_level'])?(int)$res['price_level']:null;
                if (!empty($res['reviews'])) foreach ($res['reviews'] as $rev) { if (!empty($rev['owner_reply']['author_name'])) { $owner_name=$rev['owner_reply']['author_name']; break; } }
            }
        }
        if ($website&&!$email) $email=lgScrapeEmail($website);
        // ── Deep website finder: runs only when Google Places returned no website ──
        // Tries domain slug guesses + Google search. No API cost. ~0.5s per lead.
        if (!$has_website && !$website) {
            $found = lgFindWebsite($name, $location);
            if ($found['website']) {
                $website    = $found['website'];
                $has_website = 1;
                if (!$email  && $found['email'])  $email  = $found['email'];
                if (!$phone  && $found['phone'])  $phone  = $found['phone'];
                // Reduce opp score for has_website (no longer a "no website" lead)
                // The score is recalculated below — no manual adjustment needed
            }
        }
        $opp=0;
        if (!$has_website) $opp+=50;
        if ($ratings_total>=100) $opp+=20; elseif ($ratings_total>=30) $opp+=12; elseif ($ratings_total>=10) $opp+=6;
        if ($price_level!==null) { if ($price_level>=3) $opp+=15; elseif ($price_level===2) $opp+=8; }
        if ($rating!==null&&$rating>=4.0) $opp+=10; if ($phone) $opp+=5;
        $reasons=[]; if (!$has_website) $reasons[]='No website - needs one built';
        if ($ratings_total>=30) $reasons[]=$ratings_total.' Google reviews - established';
        if ($price_level>=3) $reasons[]='Upscale business - larger budget likely';
        if ($rating>=4.5) $reasons[]='Highly rated ('.number_format((float)$rating,1).'/5)';
        if (isset($found['website'])&&$found['website']&&$has_website) $reasons[]='Website found by deep search: '.$website;
        $why=$reasons?implode('. ',$reasons):'';
        $wfc=(isset($found['website'])&&$found['website']&&$has_website)?1:0;
        $pe=$db->real_escape_string($place_id);$ne=$db->real_escape_string($name);$one=$db->real_escape_string($owner_name);
        $phe=$db->real_escape_string($phone);$ee=$db->real_escape_string($email);$ae=$db->real_escape_string($address);
        $we=$db->real_escape_string($website);$le=$db->real_escape_string($location);$ie=$db->real_escape_string($industry);
        $rt=$rating!==null?(float)$rating:'NULL';$pl=$price_level!==null?$price_level:'NULL';
        $db->query("INSERT INTO lead_gen_results (user_id,place_id,name,owner_name,phone,email,address,website,has_website,rating,ratings_total,price_level,opportunity_score,location,industry,imported,api_calls,website_found_by_crawler) VALUES ($uid,'$pe','$ne','$one','$phe','$ee','$ae','$we',$has_website,$rt,$ratings_total,$pl,$opp,'$le','$ie',0,$api_calls,$wfc) ON DUPLICATE KEY UPDATE name='$ne',owner_name='$one',phone='$phe',email='$ee',address='$ae',website='$we',has_website=$has_website,ratings_total=$ratings_total,price_level=$pl,opportunity_score=$opp,website_found_by_crawler=$wfc");
        $rid=(int)($db->insert_id?:0);
        if (!$rid&&$place_id) { $ex=@$db->query("SELECT id FROM lead_gen_results WHERE place_id='$pe' AND user_id=$uid LIMIT 1"); if ($ex) $rid=(int)($ex->fetch_assoc()['id']??0); }
        $leads[]=['id'=>$rid,'place_id'=>$place_id,'name'=>$name,'owner_name'=>$owner_name,'phone'=>$phone,'email'=>$email,'address'=>$address,'website'=>$website,'has_website'=>$has_website,'rating'=>$rating,'ratings_total'=>$ratings_total,'opportunity_score'=>$opp,'why'=>$why,'imported'=>false,'website_found_by_crawler'=>$wfc];
    }
    usort($leads,function($a,$b){ $an=!$a['has_website']?1:0;$bn=!$b['has_website']?1:0; if ($an!==$bn) return $bn-$an; return $b['opportunity_score']-$a['opportunity_score']; });
    if ($search_mode==='no_website') $leads=array_values(array_filter($leads,fn($l)=>!$l['has_website']));
    elseif ($search_mode==='high_value') $leads=array_values(array_filter($leads,fn($l)=>$l['opportunity_score']>=50));
    $real_count=count($leads); $actual_cost=round($cost_ts+($cost_det*$real_count),6);
    $le_e=$db->real_escape_string($location);$ie_e=$db->real_escape_string($industry);
    $db->query("INSERT INTO lead_gen_usage (user_id,location,industry,result_count,api_calls_used,estimated_cost) VALUES ($uid,'$le_e','$ie_e',$real_count,$api_calls,'$actual_cost')");
    logActivity('generated leads',"$industry in $location",0,"$real_count leads");
    $new_usage=lgMonthUsed($db,$uid);$newUsed=(int)$new_usage['leads'];
    echo json_encode(['ok'=>true,'leads'=>$leads,'used'=>$newUsed,'quota'=>$userQuota,'quota_source'=>$uqInfo['source'],'remaining'=>max(0,$userQuota-$newUsed),'cost'=>round((float)$new_usage['cost'],4),'budget'=>$budget_usd]);
    exit;
}

// ── GET ALL STORED ────────────────────────────────────────────────────────────
if ($action==='get_all_stored') {
    $page=max(1,(int)($_GET['page']??1)); $per_page=max(10,min(200,(int)($_GET['per_page']??50))); $offset=($page-1)*$per_page;
    $search=trim($_GET['search']??'');$loc_f=trim($_GET['location']??'');$ind_f=trim($_GET['industry']??'');
    $website=$_GET['website']??'';$imported=$_GET['imported']??'';$user_f=(int)($_GET['user_id']??0);
    $score_min=(int)($_GET['score_min']??0);$score_max=(int)($_GET['score_max']??100);
    $sort=$_GET['sort']??'id';$sort_dir=$_GET['dir']??'desc';
    $allowed_sort=['id','opportunity_score','rating','created_at','name'];
    if (!in_array($sort,$allowed_sort)) $sort='id';
    $sort_dir=$sort_dir==='asc'?'ASC':'DESC';
    $where=['1=1'];
    if (!isManager()) $where[]="r.user_id=$uid";
    elseif ($user_f)  $where[]="r.user_id=$user_f";
    if ($search!=='') { $se=$db->real_escape_string($search); $where[]="(r.name LIKE '%$se%' OR r.owner_name LIKE '%$se%' OR r.location LIKE '%$se%' OR r.industry LIKE '%$se%' OR r.phone LIKE '%$se%' OR r.email LIKE '%$se%' OR r.address LIKE '%$se%')"; }
    if ($loc_f!=='')  { $le=$db->real_escape_string($loc_f); $where[]="r.location LIKE '%$le%'"; }
    if ($ind_f!=='')  { $ie=$db->real_escape_string($ind_f); $where[]="r.industry='$ie'"; }
    if ($website==='1') $where[]="r.has_website=1"; elseif ($website==='0') $where[]="r.has_website=0";
    if ($imported==='1') $where[]="r.imported=1"; elseif ($imported==='0') $where[]="r.imported=0";
    if ($score_min>0)  $where[]="r.opportunity_score>=$score_min";
    if ($score_max<100) $where[]="r.opportunity_score<=$score_max";
    $whereSQL=implode(' AND ',$where);
    $total=(int)(@$db->query("SELECT COUNT(*) c FROM lead_gen_results r WHERE $whereSQL")->fetch_assoc()['c']??0);
    $rows=[];
    $q=@$db->query("SELECT r.id,r.name,r.owner_name,r.phone,r.email,r.address,r.website,r.has_website,r.rating,r.ratings_total,r.opportunity_score,r.location,r.industry,r.imported,r.lead_id,r.created_at FROM lead_gen_results r WHERE $whereSQL ORDER BY r.$sort $sort_dir LIMIT $per_page OFFSET $offset");
    if ($q) while ($row=$q->fetch_assoc()) $rows[]=$row;
    $locations=[];$industries=[];
    $lq=@$db->query("SELECT DISTINCT location FROM lead_gen_results WHERE location IS NOT NULL AND location!='' ORDER BY location");
    if ($lq) while ($row=$lq->fetch_assoc()) $locations[]=$row['location'];
    $iq=@$db->query("SELECT DISTINCT industry FROM lead_gen_results WHERE industry IS NOT NULL AND industry!='' ORDER BY industry");
    if ($iq) while ($row=$iq->fetch_assoc()) $industries[]=$row['industry'];
    echo json_encode(['ok'=>true,'leads'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per_page,'locations'=>$locations,'industries'=>$industries]);
    exit;
}

// ── GET SEARCH HISTORY ────────────────────────────────────────────────────────
if ($action==='get_search_history') {
    $usage_id=(int)($_GET['usage_id']??0);
    if (!$usage_id) { echo json_encode(['ok'=>false,'error'=>'Invalid usage ID']); exit; }
    $urow=@$db->query("SELECT * FROM lead_gen_usage WHERE id=$usage_id AND user_id=$uid LIMIT 1")->fetch_assoc();
    if (!$urow) { echo json_encode(['ok'=>false,'error'=>'Search record not found']); exit; }
    $le=$db->real_escape_string($urow['location']);$ie=$db->real_escape_string($urow['industry']);$created=$db->real_escape_string($urow['created_at']);
    $q=@$db->query("SELECT * FROM lead_gen_results WHERE user_id=$uid AND location='$le' AND industry='$ie' AND created_at BETWEEN DATE_SUB('$created',INTERVAL 5 MINUTE) AND DATE_ADD('$created',INTERVAL 5 MINUTE) ORDER BY id ASC LIMIT 20");
    $leads=[];if ($q) while ($row=$q->fetch_assoc()) $leads[]=$row;
    echo json_encode(['ok'=>true,'leads'=>$leads,'location'=>$urow['location'],'industry'=>$urow['industry']]);exit;
}

// ── IMPORT LEAD ───────────────────────────────────────────────────────────────
if ($action==='import_lead') {
    $rid=(int)($_POST['result_id']??0);
    $row=@$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid")->fetch_assoc();
    if (!$row) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }
    if ($row['imported']) { echo json_encode(['ok'=>false,'error'=>'Already imported']); exit; }
    $n=$row['name'];$p=$row['phone'];$em=$row['email']??'';$svc=$row['industry'];$src='other';$st='new';$pr='medium';$bc='LKR';$null=null;
    $notes="Lead Generator: {$row['industry']} in {$row['location']}.";
    if (!empty($row['owner_name'])) $notes.=" Owner: {$row['owner_name']}.";
    if ($row['address']) $notes.=" Address: {$row['address']}."; if ($row['website']) $notes.=" Website: {$row['website']}.";
    if ($row['rating']) $notes.=" Google Rating: {$row['rating']}/5."; $notes.=$row['has_website']?' Has website: Yes.':" Has website: No.";
    $stmt=$db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssdsssssssii",$n,$n,$em,$p,$src,$svc,$null,$bc,$st,$pr,$null,$null,$notes,$null,$null,$uid);
    $stmt->execute();$lid=(int)$db->insert_id;
    $db->query("UPDATE lead_gen_results SET imported=1,lead_id=$lid WHERE id=$rid");
    logActivity('imported lead',$n,$lid);
    echo json_encode(['ok'=>true,'lead_id'=>$lid,'message'=>"$n imported to CRM Leads"]);exit;
}

// ── IMPORT ALL ────────────────────────────────────────────────────────────────
if ($action==='import_all') {
    $ids=array_filter(array_map('intval',explode(',',$_POST['ids']??'')));
    if (!$ids) { echo json_encode(['ok'=>false,'error'=>'No IDs']); exit; }
    $imported_count=0;
    foreach ($ids as $rid) {
        $row=@$db->query("SELECT * FROM lead_gen_results WHERE id=$rid AND user_id=$uid AND imported=0")->fetch_assoc(); if (!$row) continue;
        $n=$row['name'];$p=$row['phone'];$em=$row['email']??'';$svc=$row['industry'];$src='other';$st='new';$pr='medium';$bc='LKR';$null=null;
        $notes="Lead Generator: {$row['industry']} in {$row['location']}.";
        if (!empty($row['owner_name'])) $notes.=" Owner: {$row['owner_name']}.";
        if ($row['address']) $notes.=" Address: {$row['address']}."; if ($row['website']) $notes.=" Web: {$row['website']}.";
        $notes.=$row['has_website']?' Has website: Yes.':" Has website: No.";
        $stmt=$db->prepare("INSERT INTO leads (name,company,email,phone,source,service_interest,budget_est,budget_currency,stage,priority,expected_close,last_contact,notes,loss_reason,assigned_to,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssdsssssssii",$n,$n,$em,$p,$src,$svc,$null,$bc,$st,$pr,$null,$null,$notes,$null,$null,$uid);
        $stmt->execute();$lid=(int)$db->insert_id;
        $db->query("UPDATE lead_gen_results SET imported=1,lead_id=$lid WHERE id=$rid");$imported_count++;
    }
    logActivity('bulk import leads','',0,"$imported_count leads");
    echo json_encode(['ok'=>true,'imported'=>$imported_count]);exit;
}

// ── BULK DELETE ───────────────────────────────────────────────────────────────
if ($action==='bulk_delete') {
    if (!isManager()) { echo json_encode(['ok'=>false,'error'=>'Permission denied']); exit; }
    $ids=array_filter(array_map('intval',explode(',',$_POST['ids']??'')));
    if (!$ids) { echo json_encode(['ok'=>false,'error'=>'No IDs provided']); exit; }
    $result=@$db->query("DELETE FROM lead_gen_results WHERE id IN(".implode(',',$ids).")");
    echo $result?json_encode(['ok'=>true,'deleted'=>$db->affected_rows]):json_encode(['ok'=>false,'error'=>'Delete failed']);exit;
}

// ── GET STATS ─────────────────────────────────────────────────────────────────
if ($action==='get_stats') {
    $globalQuota=(int)lgGet($db,'monthly_quota','300');$budget=(float)lgGet($db,'monthly_budget_usd','15.00');$api_set=lgGet($db,'google_api_key')!=='';
    $usage=lgMonthUsed($db,$uid);$uqInfo=lgUserQuota($db,$user,$globalQuota);$userQuota=$uqInfo['quota'];$usedLeads=(int)$usage['leads'];
    $trend=[];$tr=@$db->query("SELECT DATE_FORMAT(created_at,'%b') mo,DATE_FORMAT(created_at,'%Y-%m') smo,SUM(result_count) cnt,SUM(estimated_cost) cost FROM lead_gen_usage WHERE user_id=$uid AND created_at>=DATE_SUB(NOW(),INTERVAL 6 MONTH) GROUP BY mo,smo ORDER BY smo");if ($tr) $trend=$tr->fetch_all(MYSQLI_ASSOC);
    $recent=[];$rc=@$db->query("SELECT id,industry,location,result_count,estimated_cost,created_at FROM lead_gen_usage WHERE user_id=$uid ORDER BY created_at DESC LIMIT 5");if ($rc) $recent=$rc->fetch_all(MYSQLI_ASSOC);
    $total_imp=(int)(@$db->query("SELECT COUNT(*) c FROM lead_gen_results WHERE user_id=$uid AND imported=1")->fetch_assoc()['c']??0);
    $total_all=(int)(@$db->query("SELECT COUNT(*) c FROM lead_gen_results WHERE user_id=$uid")->fetch_assoc()['c']??0);
    $gr=@$db->query("SELECT COALESCE(SUM(estimated_cost),0) c FROM lead_gen_usage WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");$globalUsedCost=$gr?(float)($gr->fetch_assoc()['c']??0):0;
    echo json_encode(['ok'=>true,'used'=>$usedLeads,'quota'=>$userQuota,'quota_source'=>$uqInfo['source'],'remaining'=>max(0,$userQuota-$usedLeads),'blocked'=>$uqInfo['blocked'],'cost'=>round((float)$usage['cost'],4),'global_cost'=>round($globalUsedCost,4),'budget'=>$budget,'api_set'=>$api_set,'trend'=>$trend,'recent'=>$recent,'total_imp'=>$total_imp,'total_all'=>$total_all]);exit;
}

// ── GET QUOTA CONFIG ──────────────────────────────────────────────────────────
if ($action==='get_quota_config') {
    if (!isAdmin()) { echo json_encode(['ok'=>false,'error'=>'Admin only']); exit; }
    $cfg=lgGetQuotaConfig($db);$globalQuota=(int)lgGet($db,'monthly_quota','300');$users=[];
    $uq=@$db->query("SELECT u.id,u.name,u.email,u.role,COALESCE(m.leads,0) used_leads,COALESCE(m.cost,0) used_cost FROM users u LEFT JOIN (SELECT user_id,SUM(result_count) leads,SUM(estimated_cost) cost FROM lead_gen_usage WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW()) GROUP BY user_id) m ON m.user_id=u.id ORDER BY u.role ASC,u.name ASC");
    if ($uq) while ($row=$uq->fetch_assoc()) { $uid_str=(string)$row['id'];$eq=lgUserQuota($db,$row,$globalQuota);$row['effective_quota']=$eq['quota'];$row['quota_source']=$eq['source'];$row['user_override']=array_key_exists($uid_str,$cfg['users'])?(int)$cfg['users'][$uid_str]:null;$row['blocked']=$eq['blocked'];$users[]=$row; }
    echo json_encode(['ok'=>true,'roles'=>$cfg['roles'],'users'=>$users,'global_quota'=>$globalQuota]);exit;
}

// ── SAVE QUOTA CONFIG ─────────────────────────────────────────────────────────
if ($action==='save_quota_config') {
    if (!isAdmin()) { echo json_encode(['ok'=>false,'error'=>'Admin only']); exit; }
    $roles=['admin'=>max(0,(int)($_POST['role_admin']??300)),'manager'=>max(0,(int)($_POST['role_manager']??100)),'user'=>max(0,(int)($_POST['role_user']??30))];
    $cfg=lgGetQuotaConfig($db);$userOverrides=$cfg['users'];
    if (!empty($_POST['user_quota'])&&is_array($_POST['user_quota'])) {
        foreach ($_POST['user_quota'] as $puid=>$pval) { $puid=(string)(int)$puid;$pval=trim($pval);if ($pval===''||$pval==='-1') unset($userOverrides[$puid]); else $userOverrides[$puid]=(int)$pval; }
    }
    lgSet($db,'quota_roles',json_encode($roles),$uid);lgSet($db,'quota_users',json_encode($userOverrides),$uid);
    echo json_encode(['ok'=>true,'message'=>'Quota settings saved']);exit;
}

// ── SAVE SETTINGS ─────────────────────────────────────────────────────────────
if ($action==='save_settings'&&isAdmin()) {
    $g_key=trim($_POST['google_key']??'');$quota=max(10,min(5000,(int)($_POST['quota']??300)));$budget=max(1,min(180,(float)($_POST['budget']??15)));
    $db->query("UPDATE lead_gen_settings SET setting_val='google',updated_by=$uid WHERE setting_key='api_provider'");
    if ($g_key) { $gke=$db->real_escape_string($g_key);$db->query("UPDATE lead_gen_settings SET setting_val='$gke',updated_by=$uid WHERE setting_key='google_api_key'"); }
    $db->query("UPDATE lead_gen_settings SET setting_val=$quota,updated_by=$uid WHERE setting_key='monthly_quota'");
    $db->query("UPDATE lead_gen_settings SET setting_val=$budget,updated_by=$uid WHERE setting_key='monthly_budget_usd'");
    echo json_encode(['ok'=>true,'message'=>'Settings saved']);exit;
}

// ── TEST KEY ──────────────────────────────────────────────────────────────────
if ($action==='test_key') {
    $key=trim($_POST['google_key']??lgGet($db,'google_api_key'));
    if (!$key) { echo json_encode(['ok'=>false,'error'=>'No API key provided']); exit; }
    $raw=lgHttp("https://maps.googleapis.com/maps/api/place/textsearch/json?query=restaurant+in+London&key=$key");
    if (!$raw) { echo json_encode(['ok'=>false,'error'=>'Cannot reach Google API from your server']); exit; }
    $d=json_decode($raw,true);
    if (!empty($d['results'])) { echo json_encode(['ok'=>true,'message'=>'API key works! Found: '.$d['results'][0]['name']]); }
    elseif (($d['status']??'')==='REQUEST_DENIED') { $msg=$d['error_message']??'Request denied';echo json_encode(['ok'=>false,'error'=>strpos($msg,'Places API')!==false?"Places API not enabled.\n$msg":"Key rejected: $msg"]); }
    elseif (($d['status']??'')==='OVER_QUERY_LIMIT') { echo json_encode(['ok'=>false,'error'=>'Over query limit.']); }
    else { echo json_encode(['ok'=>false,'error'=>'Response: '.($d['status']??'unknown')]); }
    exit;
}

// ── EXPORT CSV ────────────────────────────────────────────────────────────────
if ($action==='export_csv') {
    $ids=array_filter(array_map('intval',explode(',',$_POST['ids']??'')));
    if (!$ids) { echo json_encode(['ok'=>false,'error'=>'No data']); exit; }
    $rows=@$db->query("SELECT name,owner_name,phone,email,address,website,has_website,rating,industry,location,imported FROM lead_gen_results WHERE id IN(".implode(',',$ids).") AND user_id=$uid")->fetch_all(MYSQLI_ASSOC);
    if (!$rows) { echo json_encode(['ok'=>false,'error'=>'No data']); exit; }
    $csv="Business Name,Owner Name,Phone,Email,Address,Website,Has Website,Rating,Industry,Location,Imported\n";
    foreach ($rows as $r) { $r['has_website']=$r['has_website']?'Yes':'No';$r['imported']=$r['imported']?'Yes':'No';$csv.=implode(',',array_map(fn($v)=>'"'.str_replace('"','""',(string)($v??'')).'"',array_values($r)))."\n"; }
    echo json_encode(['ok'=>true,'csv'=>$csv]);exit;
}

echo json_encode(['ok'=>false,'error'=>'Unknown action: '.$action]);