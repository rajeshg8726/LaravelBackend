<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Jobs;
use App\Services\GeminiParserService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ScrapeAtsJobs extends Command
{
    protected $signature = 'jobs:scrape-ats';
    protected $description = 'Scrape early career tech jobs from Greenhouse/Lever and parse via Groq API';

    public function handle(GeminiParserService $parser)
    {
        $this->info('Starting ATS Job Scraper...');

        $greenhouseBoards = [
            '1047games', '10beauty', '2k', '2kvegas', '31stunion', '5minlab', '66degrees', '6sense',
            'abnormalsecurity', 'absurdventures', 'acronis', 'addepar1', 'adyen', 'aegworldwide', 'aerospike', 'affinidi',
            'affinity', 'agoda', 'airbyte', 'airtable', 'aiserajobs', 'akqa', 'allergandatalabs', 'alloy',
            'alphasense', 'altentechnologyusa', 'altium', 'amplitude', 'anchorpoint', 'andurilindustries', 'animallogicsydneyhybridusdam', 'anthropic',
            'anydesk', 'apexlogic', 'apolloio', 'appier', 'appliedintuition', 'appsflyer', 'arcadiacareers', 'archetypeentertainment',
            'arenanet', 'arkoselabs', 'armada', 'aspire', 'assembled', 'assemblyai', 'assetwatch', 'asteralabs',
            'atariinc', 'atomiccartoons', 'attune', 'aurosglobal', 'automatainc', 'avathon', 'aviatrix', 'axicorpfinancialservicesptyltd',
            'axle', 'axon', 'azragames', 'badbrain', 'banyansoftware', 'bardelentertainment', 'baton', 'beautifulai',
            'betsson', 'betterup', 'bigid', 'biltrewards', 'birdygrey', 'bitgo', 'bitreactor', 'blenheimchalcotindia',
            'blinkhealth', 'blitzapp', 'bluehole', 'bluevineindia', 'bold', 'boomentertainment', 'bottomlinetechnologies', 'brandnewschool',
            'britive', 'brooklinen', 'bseglobal', 'bulletfarm', 'bungie', 'c3iot', 'calm', 'cambridgemobiletelematics',
            'canonical', 'capco', 'cargurus', 'catdaddy', 'cavnue', 'celigo', 'cerebrassystems', 'chainguard',
            'chargepoint', 'chartboost', 'christfellowship', 'cialfo', 'clever', 'clicktherapeutics', 'closedloop', 'cloudchamberen',
            'clutch', 'coactivesystems', 'codeandtheory', 'coinbase', 'collaborativerobotics', 'commerceiq', 'conga', 'contentstack',
            'conviva', 'convoso', 'cookunity', 'coursera', 'crashplan', 'criticalmass', 'crunchyroll', 'crystaldynamics',
            'daredrop', 'databricks', 'dataiku', 'decisions', 'deepmind', 'definitivehcindia', 'delasport', 'deliveroo',
            'demandbase', 'dept', 'detroitlions', 'devrev', 'dexory', 'dialpad', 'dice', 'digicert',
            'digitalextremes', 'diligentcorporation', 'disco', 'discord', 'dmarket', 'domo', 'dots', 'dpsgames',
            'droit', 'dropbox', 'druva', 'duolingo', 'dydx', 'dynamisinc', 'earnin', 'easygo',
            'easyship', 'eclinicalsolutions', 'edglrd', 'embrace', 'encore', 'engagedmd', 'enterrasolutions', 'entrupy',
            'envoyglobalinc', 'eositsolutions', 'epicgames', 'eqvilentjobs', 'esusu', 'ethernovia', 'ethos', 'ethoslife',
            'eventbriteinc', 'everlane', 'everlaw', 'everstreamanalytics', 'evoplaygames', 'falconx', 'fandom', 'fanduel',
            'fantasticpixelcastle', 'fccincinnati', 'fictiv', 'figma', 'firaxis', 'firesprite', 'firewalk', 'fireworksai',
            'fivetran', 'flocksafety', 'fluxon', 'foratravel', 'formlabs', 'forte', 'fortra', 'fortune',
            'fourhands', 'foursquare26', 'freshprints', 'frgjobs', 'g2crowd', 'gatherai', 'genea', 'genies',
            'genpopinteractiveinc', 'gitlab', 'glance', 'gleanwork', 'globalhealthcareexchangeinc', 'globalizationpartners', 'glossgenius', 'goatgroup',
            'godaddy', 'gofundme', 'goguardian', 'gomotive', 'goodjobgames', 'goop', 'govini', 'gramgamescareers',
            'gravitywell', 'greenwaveradios', 'greenworkssunriseglobalmarketing', 'grindr', 'groupon', 'growe', 'groww', 'gruve',
            'guerrilla', 'guild', 'gusto', 'hackerrank', 'halcyon', 'hangar13', 'hardsuitlabs', 'harmonic',
            'hasbro', 'haven', 'hbstudios', 'headoutlinkedin', 'headoutreferrals', 'highdive', 'highradius', 'hightouch',
            'honor', 'hopskipdrive', 'housemarque', 'hovercraft', 'hoyoverse', 'huddle01', 'hudl', 'hunterdouglas',
            'hypixelstudios', 'ibkr', 'ibkrexternal', 'imagendarystudios', 'imply', 'imprint', 'ingenuitystudios', 'ingenuitystudiosuk',
            'inmobi', 'innophaseiot', 'innovecs', 'insomniac', 'inspiren', 'instabase', 'instacart', 'instawork',
            'interfaceai', 'interviewkickstart', 'inthepocket', 'inworldai', 'ixllearning', 'jackalypticgames', 'janestreet', 'jarofsparks',
            'jetbrains', 'jfrog', 'jumio', 'justanswer', 'kailera', 'kaleris', 'kalshi', 'kaseya',
            'kayak', 'kinetik', 'kintentinc', 'klaviyo', 'koch', 'krafton', 'kraftonamericas', 'kraftonindia',
            'kulficollective', 'laclippers', 'landor', 'launchdarkly', 'legion', 'letsgetchecked', 'levelex', 'lightforceorthodontics',
            'lilasciences', 'lindenlab', 'lirio', 'litmus46', 'liveperson', 'logicmonitor', 'lokalise', 'lovable',
            'macrometa', 'magicleapinc', 'make', 'mangopay', 'manifest', 'mark43', 'masterclass', 'matteprojects',
            'maxsecure', 'mcafee', 'mediatonic', 'meetelise', 'memryx', 'mercury', 'merqube', 'micoworks',
            'midsummerstudios', 'miqdigital', 'mitratech', 'mixpanel', 'mm', 'mobentertainment', 'mobius', 'mojangab',
            'moloco', 'moniepoint', 'monks', 'monsterenergy', 'monzo', 'moveworks', 'mozilla', 'mpowerfinancing',
            'myfitnesspal', 'myntra', 'mythicalgames', 'n26', 'ncsoftwest', 'neonkoi', 'netbrain', 'neteasegames',
            'netradyne', 'netskope', 'neuralink', 'neverforgetgames', 'newglobesandbox', 'newrelic', 'nextdoor', 'nextiva',
            'niantic', 'nice', 'nightdivestudios', 'nightfall', 'nimblegiant', 'nksecuritiesresearch', 'nomiso', 'nordeus',
            'notion', 'novo', 'nubank', 'octagon', 'ogilvy', 'okta', 'okx', 'olipop',
            'oliverplus', 'oliverseapac', 'onarchipelago', 'oneimaging', 'onelocal', 'onetrust', 'onistudios', 'openai',
            'opendoor', 'oplabs', 'oportun', 'optimism', 'orderlynetwork', 'orioninnovation', 'oshihealth', 'osmosisdex',
            'othersideentertainment', 'oura', 'outfit7', 'outschool', 'overdare', 'paciolan', 'panicbutton', 'pay2dc',
            'paypay', 'pelago', 'penninteractive', 'performio', 'perplexityai', 'perrystreetsoftware', 'philo', 'phonepe',
            'pika', 'pingidentity', 'pinterest', 'playgiginc', 'playq', 'playstationlondonstudio', 'pocketgems', 'pokemoncareers',
            'polyai', 'polygence', 'poppulo', 'porchindia', 'postman', 'powerdigitalmarketing', 'prodigal', 'profound',
            'propel', 'prophecysimpledatalabs', 'psyonix', 'pubgemea', 'pubgmadison', 'pubgsanramon', 'pubgsm', 'pubmatic',
            'pulley', 'purestorage', 'putnamassociatesllc', 'quilt', 'quince', 'quinstreet', 'rackner', 'rapp',
            'realchemistry', 'reddit', 'reltio', 'remotecom', 'resi', 'rev', 'rockstargames', 'roku',
            'roll7', 'roller', 'rtbhouse', 'rubrik', 'rushdownstudios', 'rushstreetinteractive', 'safariai', 'sagent',
            'saltxc', 'samainc', 'sambanovasystems', 'samsara', 'samsungsemiconductor', 'saturn', 'savagegamestudios', 'scaleai',
            'scoutmotors', 'seam', 'seamount', 'shein', 'shield', 'shopmy', 'shrapnelstudio', 'siei',
            'sigmacomputing', 'simplify360', 'simplisafe', 'singlestore', 'sirenopt', 'sixfold', 'skillsoft', 'skillzinc',
            'skylotechnologies', 'slice', 'smartbear', 'snapmobileinc', 'snorkelai', 'snyk', 'socialpoint', 'sofi',
            'sohohouseco', 'solsten', 'sonicwall', 'sonyinteractiveentertainmentglobal', 'sonymusicasiacareers', 'sonymusicentertainment', 'sonypicturesanimation', 'soundcloud71',
            'sourcegraph91', 'spacex', 'spauldingridge', 'specterops', 'sphereentertainment', 'splice', 'spotoncorporate', 'stabilityai',
            'stackblitz', 'stacklok', 'stage', 'starburst', 'stockx', 'stripe', 'studiokraftonboard', 'suki',
            'sumologic', 'surveymonkey', 'sustainabletalent', 'taboola', 'taketwo', 'targetbase', 'techholding', 'technisyscareers',
            'techstars57', 'tegnainc', 'tekion', 'telesign', 'tellius', 'telnyx54', 'temporaltechnologies', 'tenstorrent',
            'thatsnomoonentertainment', 'thena', 'thenewyorktimes', 'theorchard', 'thiess', 'thinkacademyus', 'thousandeyes', 'threespacelab',
            'thumbtack', 'tide', 'tminuszero', 'topspot', 'tornbannerstudios', 'towerresearchcapital', 'townsquaremedia', 'traderepublic',
            'trailerpark', 'tripledotstudios', 'tripwireinteractive', 'trove', 'trovebrands', 'truecaller', 'truefoundry', 'truelogic',
            'ttcglobal', 'turbo', 'turbotenant', 'turtlerockstudios', 'twitch', 'udemy', 'undeadlabsllc', 'unispace',
            'uniswaplabs', 'unknownworlds', 'upgrade', 'upwork', 'ushur', 'ustwogames', 'vaco', 'valtech',
            'vast', 'vaynermedia', 'vdc', 'vectornorth', 'vercel', 'verifone', 'vgw', 'vimeo',
            'virtualhealth', 'visualconcepts', 'volleythat', 'vyro', 'wargamingwelcometothejungle', 'wayve', 'well', 'wfclainc',
            'whalarinc', 'whatnot', 'whatwapp', 'wildlifestudios', 'wirewheel', 'wizardsofthecoast', 'wolfjawstudios', 'wongdoody',
            'wooga', 'woonetwork', 'workato', 'workco', 'worldsuntold', 'wrike', 'xgames', 'yext',
            'yugalabs', 'zenoti', 'zetaglobal', 'zinnia', 'zocdoc', 'zoominfo', 'zscaler', 'zuora'
        ];
        
        $leverBoards = [
            'a', 'acceldata', 'accurate', 'actian', 'aeratechnology', 'aidash', 'aifund', 'airshipsyndicate',
            'aledade', 'alictus', 'amanotes', 'analogfolk', 'anavationllc', 'animocabrands', 'aofl', 'apollographql',
            'applike', 'appzen', 'arootah', 'athena-education', 'attentive', 'avalanchestudios', 'axiomzen', 'azul',
            'balbix', 'barbaricum', 'bazaarvoice', 'beghouconsulting', 'bentoboxent', 'beyond-creative', 'bhvr', 'biggergames',
            'bigtime', 'binance', 'blackbirdinteractive', 'blinkux', 'blur', 'bold-orange', 'bonfirestudios', 'bounteous',
            'branch', 'brightedge', 'brightmachines', 'brillio-2', 'cambiumnetworks', 'cision', 'clari', 'clevertap',
            'coda', 'codecombat', 'codewaystudios', 'cognite', 'connext-network', 'convai-technologies-inc', 'conversenow', 'coupa',
            'craft', 'creadits', 'cred', 'crytek', 'cyara', 'cypher-games', 'czinger', 'dailywire',
            'dazn', 'demiurgestudios', 'disruptivegames', 'dnb', 'doola', 'doxel', 'dragonarmy', 'dreamgames',
            'dreamsports', 'drivemode', 'drivetrain', 'dynastystudios', 'easybrain', 'echtra', 'economicmodeling', 'egen',
            'elodiegames', 'employ', 'entrata', 'epifi', 'equativ', 'esper', 'explodingkittens', 'expopulus',
            'extremenetworks', 'f16y', 'fairmatic', 'fanatee', 'fanatics', 'fantasy', 'fathomradiant', 'feldinc',
            'filevine', 'finch', 'findem', 'flawlessai', 'fliff', 'flipfit', 'flowlife', 'fluence',
            'fullstacklabs', 'fyusion', 'gametime', 'golfscopeinc', 'gotogroup', 'granicus', 'hdworks', 'hhaexchange',
            'highpointaerotech', 'highspot', 'hingehealth', 'hirezstudios', 'hiver', 'hologram', 'hopelab', 'horizon',
            'hotstar', 'icertis', 'illumination', 'illumix', 'imaginitesoft', 'immutable', 'improbable', 'inflexion',
            'inkitt', 'instrument', 'instrumentl', 'ion', 'ionicpartners', 'jagex', 'jamcity', 'jar-app',
            'jellysmack', 'jiostar', 'jmawireless', 'joinzoe', 'jumpcloud', 'juno', 'kabam', 'kapwing',
            'kolibrigames', 'koombea', 'kyruushealth', 'larian', 'legacylabs', 'levelai', 'life', 'limitbreak',
            'loaded', 'loftorbital', 'loopgames', 'lumapictures', 'lunchboxentertainment', 'luni', 'm33', 'madbox',
            'madisonlogic', 'magicgames', 'magnopus', 'mashgin', 'matchgroup', 'mediaocean', 'mendix', 'metatheory',
            'metlife', 'mindtickle', 'mineral', 'minted', 'mips', 'mistplay', 'modulate', 'mountaintop',
            'nahc', 'neednova', 'netomi', 'nielsen', 'ninjavan', 'nisum', 'nium', 'noice',
            'normalyze', 'observeai', 'octopus', 'oliverwyman', 'onehouse', 'opengov', 'opinov8', 'pachama',
            'paradoxum-gg', 'parallelwireless', 'payactiv', 'paytm', 'peakgames', 'penumbrainc', 'peoplefun', 'pinegames',
            'plaid', 'plailabsinc', 'playvs', 'poki', 'portcast', 'porter', 'ppfa', 'prismlabs',
            'prodigyeducation', 'proofofplay', 'qcells', 'quixel', 'quizizz', 'rackspace', 'ramenvr', 'raptstudio',
            'redhorsecorp', 'regentcraft', 'regrello', 'reli-sh', 'remedyentertainment', 'rise', 'rivosinc', 'robomq',
            'roofstacks', 'rovio-2', 'rubyplay', 'rw1', 'safe', 'sandboxvr', 'saviynt', 'scanlinevfx',
            'scopear', 'seedify-fund', 'sesame', 'shieldai', 'shyftlabs', 'sitetracker', 'skyboxlabs', 'skydance',
            'smarsh', 'snappr', 'sofarsounds', 'sonarsource', 'sporty', 'spotify', 'sprucesystems', 'spyke-games',
            'stackblitz', 'stoic', 'storygrounds', 'sumo-digital', 'super-com', 'swissborg', 'swordhealth', 'sytac',
            'tactilegames', 'tala', 'theorycraftgames', 'thirdwavelabs', 'threatconnect', 'threattec', 'titan', 'titmouse',
            'tokenmetrics', 'toku', 'toptal', 'trackvfx', 'trendyol', 'ultra', 'umanaiainteractive', 'uniphore',
            'unknownworlds', 'upstox', 'uptycs', 'useinsider', 'varni-labs', 'vendavo', 'viacom18', 'vidsy',
            'volka', 'voodoo', 'waveapps', 'webfx', 'whoop', 'wildlight', 'wisk', 'worldmakers',
            'wr', 'xepelin', 'xsolla', 'zeeco', 'zerofox', 'zeta', 'zoox', 'zuru'
        ];

        // Shuffle lists to ensure we pull from different companies on each run
        shuffle($greenhouseBoards);
        shuffle($leverBoards);

        // ── Quota: India-first strategy ────────────────────────────────
        // 70% India + 30% Remote/Global = ensures mostly relevant jobs for Indian users
        $maxGreenhouse = 3;
        $maxLever = 3;
        $greenhouseAdded = 0;
        $leverAdded = 0;
        $totalAdded = 0;

        // ── Track ONLY boards that actually yielded a result today ──
        $cacheKey = 'scraper_processed_boards_' . date('Y-m-d');
        $processedBoards = Cache::get($cacheKey, []);

        // ═══════════════════════════════════════════════════════════════
        // GREENHOUSE SCRAPING
        // ═══════════════════════════════════════════════════════════════
        // PASS 1: India-only jobs (fill up to 70% of quota)
        // PASS 2: Remote/Global jobs (fill remaining quota)
        $indiaQuotaGH = (int) ceil($maxGreenhouse * 0.7); // 6 India jobs
        $remoteQuotaGH = $maxGreenhouse - $indiaQuotaGH;   // 2 Remote jobs
        $indiaAddedGH = 0;
        $remoteAddedGH = 0;

        foreach ([1, 2] as $pass) {
            foreach ($greenhouseBoards as $board) {
                if ($greenhouseAdded >= $maxGreenhouse) break;
                if ($pass === 1 && $indiaAddedGH >= $indiaQuotaGH) break;
                if ($pass === 2 && $remoteAddedGH >= $remoteQuotaGH) break;

                // Skip boards already processed today
                if (in_array("gh_{$board}", $processedBoards)) {
                    continue;
                }

                $this->info("[Pass {$pass}] Fetching Greenhouse jobs for: {$board}");

                try {
                    $response = Http::timeout(15)->get("https://boards-api.greenhouse.io/v1/boards/{$board}/jobs");
                } catch (\Exception $e) {
                    $this->error("cURL/Connection error fetching Greenhouse board '{$board}': " . $e->getMessage());
                    continue;
                }

                if (!$response->successful()) {
                    $this->warn("Greenhouse API returned status {$response->status()} for board '{$board}'");
                    continue;
                }

                $jobs = $response->json('jobs') ?? [];

                foreach ($jobs as $job) {
                    if ($greenhouseAdded >= $maxGreenhouse) break;
                    if ($pass === 1 && $indiaAddedGH >= $indiaQuotaGH) break;
                    if ($pass === 2 && $remoteAddedGH >= $remoteQuotaGH) break;

                    $title = strtolower($job['title']);
                    $location = $job['location']['name'] ?? 'Remote';

                    // ── Location Filter: Pass 1 = India ONLY, Pass 2 = Remote/Global ONLY ──
                    $isIndia = $this->isLocationInIndia($location);
                    if ($pass === 1 && !$isIndia) continue;
                    if ($pass === 2 && $isIndia) continue; // Already handled in pass 1
                    if ($pass === 2 && !$this->isRemoteOrGlobal($location)) continue;

                    // ── Filter: Must be a technical/software role ──
                    if (!$this->isTechnicalSoftwareRole($job['title'])) {
                        continue;
                    }

                    // ── Early Skip: Senior/Leadership titles (saves API calls) ──

                    if (preg_match('/\b(senior|sr\\.|staff|principal|lead|manager|director|vp|avp|head of|architect|distinguished|fellow)\b/i', $title)) {

                        $this->line("   -> Skipped (title): '{$job['title']}' is senior/leadership.");

                        continue;

                    }



                    // ── Duplicate check by joblink ──
                    if (Jobs::withoutGlobalScope('published')->where('joblink', $job['absolute_url'])->exists()) {
                        continue;
                    }

                    // ── Duplicate check by role + company combo ──
                    if (Jobs::withoutGlobalScope('published')
                        ->where('role', $job['title'])
                        ->where('title', $board)
                        ->exists()
                    ) {
                        $this->line("   -> Skipped: Duplicate role+company combo.");
                        continue;
                    }

                    $this->info("Parsing matching job: {$job['title']} ({$location})");

                    // Fetch full job details
                    try {
                        $detailRes = Http::timeout(15)->get("https://boards-api.greenhouse.io/v1/boards/{$board}/jobs/{$job['id']}");
                    } catch (\Exception $e) {
                        $this->error("cURL/Connection error fetching Greenhouse job details: " . $e->getMessage());
                        continue;
                    }

                    if (!$detailRes->successful()) {
                        $this->warn("Greenhouse details API returned status {$detailRes->status()} for job ID {$job['id']}");
                        continue;
                    }

                    $contentHtml = $detailRes->json('content') ?? '';
                    if (empty($contentHtml)) continue;

                    // ── Extract experience requirement (don't filter, just detect) ──
                    $experienceYears = $this->extractExperienceYears($contentHtml);

                    // ── Call AI (Gemini/Groq) to parse ──
                    $parsedFields = $parser->parseJobDescription($contentHtml, $job['title'], $location);

                    $this->line("   -> Sleeping 4s for API rate limits...");
                    sleep(4);

                    if ($parsedFields === null) {
                        $this->warn("   -> AI parsing failed for: {$job['title']}");
                        continue;
                    }

                    // ── Determine experience level and batches field ──
                    $expCategory = $this->classifyExperienceLevel($title, $parsedFields, $experienceYears);
                    $batchesField = $this->buildBatchesField($parsedFields, $expCategory, $experienceYears);

                    // ── Skip experienced (5+ years) roles — only keep intern, fresher, mid-level ──
                    if ($expCategory === 'experienced') {
                        $yrsDisplay = $experienceYears !== null ? "{$experienceYears}+" : 'title-based';
                        $this->line("   -> Skipped: Classified as 'experienced' ({$yrsDisplay} yrs). Only intern/fresher/mid-level allowed.");
                        continue;
                    }

                    $jobtypeValue = $parsedFields['companyType'] ?? null;
                    $jobtypeId = $this->getCompanyTypeId($jobtypeValue);
                    $locationId = $this->getLocationId($location);
                    $expLevelId = $this->getExpLevelId($expCategory, $experienceYears);

                    Jobs::create([
                        'title' => $parsedFields['seoTitle'] ?? $job['title'],
                        'role' => $job['title'],
                        'location' => $location,
                        'joblink' => $job['absolute_url'],
                        'description' => $parsedFields['jobDescription'] ?? strip_tags($contentHtml),
                        'rolesAndResponsibilities' => $parsedFields['rolesAndResponsibilities'] ?? null,
                        'requirements' => $parsedFields['requirements'] ?? null,
                        'niceToHave' => $parsedFields['niceToHave'] ?? null,
                        'eligibility' => $parsedFields['eligibility'] ?? null,
                        'pay' => $parsedFields['estimatedPayRange'] ?? null,
                        'batches' => $batchesField,
                        'status' => 'draft',
                        'jobtype' => $jobtypeId,
                        'jobbycity' => $locationId,
                        'jobexplevel' => $expLevelId,
                    ]);

                    $greenhouseAdded++;
                    $totalAdded++;
                    if ($isIndia) $indiaAddedGH++; else $remoteAddedGH++;
                    $this->info("✓ Added Greenhouse Draft ({$totalAdded}/6) [{$expCategory}] - {$job['title']} [{$location}]");

                    // Mark board as processed after it produces a job
                    if (!in_array("gh_{$board}", $processedBoards)) {
                        $processedBoards[] = "gh_{$board}";
                        Cache::put($cacheKey, $processedBoards, now()->endOfDay());
                    }
                }
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // LEVER SCRAPING
        // ═══════════════════════════════════════════════════════════════
        $indiaQuotaLV = (int) ceil($maxLever * 0.7); // 3 India jobs
        $remoteQuotaLV = $maxLever - $indiaQuotaLV;   // 1 Remote job
        $indiaAddedLV = 0;
        $remoteAddedLV = 0;

        foreach ([1, 2] as $pass) {
            foreach ($leverBoards as $board) {
                if ($leverAdded >= $maxLever) break;
                if ($pass === 1 && $indiaAddedLV >= $indiaQuotaLV) break;
                if ($pass === 2 && $remoteAddedLV >= $remoteQuotaLV) break;

                // Skip boards already processed today
                if (in_array("lv_{$board}", $processedBoards)) {
                    continue;
                }

                $this->info("[Pass {$pass}] Fetching Lever jobs for: {$board}");

                try {
                    $response = Http::timeout(15)->get("https://api.lever.co/v0/postings/{$board}");
                } catch (\Exception $e) {
                    $this->error("cURL/Connection error fetching Lever board '{$board}': " . $e->getMessage());
                    continue;
                }

                if (!$response->successful()) {
                    $this->warn("Lever API returned status {$response->status()} for board '{$board}'");
                    continue;
                }

                $jobs = $response->json() ?? [];

                foreach ($jobs as $job) {
                    if ($leverAdded >= $maxLever) break;
                    if ($pass === 1 && $indiaAddedLV >= $indiaQuotaLV) break;
                    if ($pass === 2 && $remoteAddedLV >= $remoteQuotaLV) break;

                    $title = strtolower($job['text'] ?? '');
                    $location = $job['categories']['location'] ?? 'Remote';

                    // ── Location Filter: Pass 1 = India ONLY, Pass 2 = Remote/Global ONLY ──
                    $isIndia = $this->isLocationInIndia($location);
                    if ($pass === 1 && !$isIndia) continue;
                    if ($pass === 2 && $isIndia) continue;
                    if ($pass === 2 && !$this->isRemoteOrGlobal($location)) continue;

                    // ── Filter: Must be a technical/software role ──
                    if (!$this->isTechnicalSoftwareRole($job['text'] ?? '')) {
                        continue;
                    }

                    // ── Early Skip: Senior/Leadership titles (saves API calls) ──

                    if (preg_match('/\b(senior|sr\\.|staff|principal|lead|manager|director|vp|avp|head of|architect|distinguished|fellow)\b/i', $title)) {

                        $this->line("   -> Skipped (title): '{$job['text']}' is senior/leadership.");

                        continue;

                    }



                    // ── Duplicate check by joblink ──
                    if (Jobs::withoutGlobalScope('published')->where('joblink', $job['hostedUrl'])->exists()) {
                        continue;
                    }

                    // ── Duplicate check by role + company combo ──
                    if (Jobs::withoutGlobalScope('published')
                        ->where('role', $job['text'])
                        ->where('title', $board)
                        ->exists()
                    ) {
                        $this->line("   -> Skipped: Duplicate role+company combo.");
                        continue;
                    }

                    $this->info("Parsing matching Lever job: {$job['text']} ({$location})");

                    // Build content: prefer plain text, append lists
                    $contentHtml = $job['descriptionPlain'] ?? $job['description'] ?? '';
                    if (isset($job['lists']) && is_array($job['lists'])) {
                        foreach ($job['lists'] as $list) {
                            $contentList = $list['content'] ?? [''];
                            if (!is_array($contentList)) {
                                $contentList = [$contentList];
                            }
                            $contentHtml .= "\n" . ($list['text'] ?? '') . "\n" . implode("\n", array_map('strip_tags', $contentList));
                        }
                    }

                    if (empty($contentHtml)) continue;

                    // ── Extract experience requirement (don't filter, just detect) ──
                    $experienceYears = $this->extractExperienceYears($contentHtml);

                    // ── Call AI (Gemini/Groq) to parse ──
                    $parsedFields = $parser->parseJobDescription($contentHtml, $job['text'], $location);

                    $this->line("   -> Sleeping 4s for API rate limits...");
                    sleep(4);

                    if ($parsedFields === null) {
                        $this->warn("   -> AI parsing failed for: {$job['text']}");
                        continue;
                    }

                    // ── Determine experience level and batches field ──
                    $expCategory = $this->classifyExperienceLevel($title, $parsedFields, $experienceYears);
                    $batchesField = $this->buildBatchesField($parsedFields, $expCategory, $experienceYears);

                    // ── Skip experienced (5+ years) roles — only keep intern, fresher, mid-level ──
                    if ($expCategory === 'experienced') {
                        $yrsDisplay = $experienceYears !== null ? "{$experienceYears}+" : 'title-based';
                        $this->line("   -> Skipped: Classified as 'experienced' ({$yrsDisplay} yrs). Only intern/fresher/mid-level allowed.");
                        continue;
                    }

                    $jobtypeValue = $parsedFields['companyType'] ?? null;
                    $jobtypeId = $this->getCompanyTypeId($jobtypeValue);
                    $locationId = $this->getLocationId($location);
                    $expLevelId = $this->getExpLevelId($expCategory, $experienceYears);

                    Jobs::create([
                        'title' => $parsedFields['seoTitle'] ?? $job['text'],
                        'role' => $job['text'],
                        'location' => $location,
                        'joblink' => $job['hostedUrl'],
                        'description' => $parsedFields['jobDescription'] ?? strip_tags($contentHtml),
                        'rolesAndResponsibilities' => $parsedFields['rolesAndResponsibilities'] ?? null,
                        'requirements' => $parsedFields['requirements'] ?? null,
                        'niceToHave' => $parsedFields['niceToHave'] ?? null,
                        'eligibility' => $parsedFields['eligibility'] ?? null,
                        'pay' => $parsedFields['estimatedPayRange'] ?? null,
                        'batches' => $batchesField,
                        'status' => 'draft',
                        'jobtype' => $jobtypeId,
                        'jobbycity' => $locationId,
                        'jobexplevel' => $expLevelId,
                    ]);

                    $leverAdded++;
                    $totalAdded++;
                    if ($isIndia) $indiaAddedLV++; else $remoteAddedLV++;
                    $this->info("✓ Added Lever Draft ({$totalAdded}/12) [{$expCategory}] - {$job['text']} [{$location}]");

                    // Mark board as processed after it produces a job
                    if (!in_array("lv_{$board}", $processedBoards)) {
                        $processedBoards[] = "lv_{$board}";
                        Cache::put($cacheKey, $processedBoards, now()->endOfDay());
                    }
                }
            }
        }

        $indiaTotal = $indiaAddedGH + $indiaAddedLV;
        $remoteTotal = $remoteAddedGH + $remoteAddedLV;
        $this->info("═══════════════════════════════════════════════");
        $this->info("Scraping complete. Added {$totalAdded} new jobs (Greenhouse: {$greenhouseAdded}, Lever: {$leverAdded}).");
        $this->info("India: {$indiaTotal} | Remote/Global: {$remoteTotal} | Boards processed today: " . count($processedBoards));
        return Command::SUCCESS;
    }

    /**
     * Determines if a job title is a technical/software role.
     * Uses a strict blacklist + whitelist approach.
     */
    private function isTechnicalSoftwareRole(string $title): bool
    {
        $title = strtolower($title);

        // 1. Exclude Non-technical roles (Blacklist)
        $blacklistRegex = '/\b(support|helpdesk|help desk|customer|sales|marketing|hr|human resources|recruiter|recruiting|talent|operations|ops|designer|design|finance|accounting|accountant|legal|writer|content|admin|administrator|coordinator|executive|assistant|business analyst|product manager|product owner|consultant|strategist|compliance|media|copywriter|editor|purchasing|buyer|logistics|supply chain|growth|social media|pr |public relations|receptionist|clerk|secretary)\b/i';
        if (preg_match($blacklistRegex, $title)) {
            return false;
        }

        // 2. Include Technical / Software role keywords (Whitelist)
        $whitelistRegex = '/\b(software|developer|engineer|sde|programmer|coder|frontend|backend|fullstack|full-stack|devops|cloud|qa|test|testing|sdet|data|analytics|machine learning|ml|ai|artificial intelligence|systems|network|security|cybersecurity|database|db|infrastructure|mobile|android|ios|sre|platform|site reliability|automation|embedded|firmware|hardware|computer science|cs|it |information technology)\b/i';
        if (preg_match($whitelistRegex, $title)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a location is strictly in India.
     */
    private function isLocationInIndia(string $location): bool
    {
        $location = strtolower($location);
        $indiaKeywords = [
            'india', 'bangalore', 'bengaluru', 'hyderabad', 'pune', 'mumbai', 
            'delhi', 'new delhi', 'noida', 'gurugram', 'gurgaon', 'chennai', 
            'kolkata', 'ahmedabad', 'thiruvananthapuram', 'trivandrum', 'kochi',
            'indore', 'chandigarh', 'jaipur', 'lucknow', 'coimbatore', 'nagpur',
            'visakhapatnam', 'vizag', 'bhubaneswar', 'mangalore', 'mysore', 'mysuru'
        ];
        foreach ($indiaKeywords as $keyword) {
            if (str_contains($location, $keyword)) return true;
        }
        return false;
    }

    /**
     * Checks if a location is Remote or at a global tech hub.
     */
    private function isRemoteOrGlobal(string $location): bool
    {
        $location = strtolower($location);
        $remoteKeywords = ['remote', 'anywhere', 'global', 'worldwide', 'distributed', 'flexible', 'work from home', 'wfh'];
        foreach ($remoteKeywords as $keyword) {
            if (str_contains($location, $keyword)) return true;
        }
        if (empty(trim($location))) return true;
        return false;
    }

    /**
     * Extracts the experience requirement in years from job description text.
     * Returns null if not found, or the max years mentioned.
     */
    private function extractExperienceYears(string $contentHtml): ?int
    {
        $text = strtolower(strip_tags($contentHtml));
        $maxYears = null;

        // Pattern: "X-Y years" or "X to Y years"
        if (preg_match_all('/(\d+)\s*(?:-|to)\s*(\d+)\s*\+?\s*(?:years?|yrs?)\b/i', $text, $matches)) {
            foreach ($matches[2] as $upper) {
                $val = (int) $upper;
                if ($maxYears === null || $val > $maxYears) $maxYears = $val;
            }
        }

        // Pattern: "X+ years" or "X years of experience"
        if (preg_match_all('/\b(\d+)\s*\+?\s*(?:years?|yrs?)\s+(?:of\s+)?(?:relevant\s+|professional\s+|hands[- ]on\s+|industry\s+)?experience\b/i', $text, $matches)) {
            foreach ($matches[1] as $y) {
                $val = (int) $y;
                if ($maxYears === null || $val > $maxYears) $maxYears = $val;
            }
        }

        // Pattern: "minimum X years" or "at least X years"
        if (preg_match_all('/(?:minimum|at\s+least|min\.?)\s*(\d+)\s*\+?\s*(?:years?|yrs?)/i', $text, $matches)) {
            foreach ($matches[1] as $y) {
                $val = (int) $y;
                if ($maxYears === null || $val > $maxYears) $maxYears = $val;
            }
        }

        return $maxYears;
    }

    /**
     * Classifies the experience level: intern, fresher, mid-level, or experienced.
     * 
     * Priority order:
     *   1. Title keywords for intern/fresher (high confidence)
     *   2. Title keywords for senior roles (high confidence)
     *   3. Extracted experience years from description (most reliable for mid-level vs experienced)
     *   4. Groq LLM classification (last resort, often unreliable)
     */
    private function classifyExperienceLevel(string $title, array $parsedFields, ?int $experienceYears): string
    {
        // ── Step 1: Title clearly says intern/fresher → always trust this ──
        if (Str::contains($title, ['intern', 'internship'])) return 'intern';
        if (Str::contains($title, ['fresher', 'trainee', 'apprentice', 'new grad'])) return 'fresher';

        // ── Step 2: Title clearly says senior/staff/manager/director → experienced ──
        if (Str::contains($title, ['senior', 'sr.', 'staff', 'principal', 'lead', 'manager', 'director', 'vp', 'head of'])) {
            return 'experienced';
        }

        // ── Step 3: Use extracted experience years (MOST RELIABLE for mid-level vs experienced) ──
        // This takes priority over Groq because Groq often misclassifies 2-3 year roles as "experienced"
        if ($experienceYears !== null) {
            if ($experienceYears <= 0) return 'fresher';
            if ($experienceYears <= 2) return 'fresher';
            if ($experienceYears <= 5) return 'mid-level';
            return 'experienced';
        }

        // ── Step 4: Groq classification (last resort, only if no years were extracted) ──
        if (isset($parsedFields['jobRoleCategory'])) {
            $cat = strtolower($parsedFields['jobRoleCategory']);
            if (str_contains($cat, 'intern')) return 'intern';
            if (str_contains($cat, 'fresher')) return 'fresher';
            // NOTE: We do NOT trust Groq's "experienced" classification here
            // because it's frequently wrong. If Groq says experienced but
            // no years were extracted, default to mid-level to be safe.
            if (str_contains($cat, 'experienced')) return 'mid-level';
        }

        return 'fresher'; // Default if nothing else matched
    }

    /**
     * Builds the batches field: includes passout batches + experience requirement.
     */
    private function buildBatchesField(array $parsedFields, string $expCategory, ?int $experienceYears): string
    {
        $parts = [];

        // Add passout batch info from Groq if available
        if (!empty($parsedFields['eligibleBatches'])) {
            $parts[] = $parsedFields['eligibleBatches'];
        }

        // Add experience requirement label
        if ($expCategory === 'intern') {
            $parts[] = 'Internship (No experience required)';
        } elseif ($expCategory === 'fresher') {
            $parts[] = '0-2 years experience';
        } elseif ($expCategory === 'mid-level') {
            $yrs = $experienceYears ?? 4;
            $parts[] = "2-{$yrs} years experience required";
        } elseif ($expCategory === 'experienced') {
            $yrs = $experienceYears ?? 5;
            $parts[] = "{$yrs}+ years experience required";
        }

        return implode(' | ', $parts) ?: '2024, 2025 passout';
    }

    /**
     * Maps location string to LocationCat ID.
     */
    private function getLocationId(string $location): int
    {
        $loc = strtolower($location);
        if (str_contains($loc, 'bengaluru') || str_contains($loc, 'bangalore')) return 1;
        if (str_contains($loc, 'noida') || str_contains($loc, 'delhi') || str_contains($loc, 'new delhi')) return 2;
        if (str_contains($loc, 'gurgaon') || str_contains($loc, 'gurugram') || str_contains($loc, 'haryana')) return 3;
        if (str_contains($loc, 'remote') || str_contains($loc, 'anywhere') || str_contains($loc, 'work from home')) return 4;
        if (str_contains($loc, 'hyderabad') || str_contains($loc, 'secunderabad')) return 5;
        if (str_contains($loc, 'chennai') || str_contains($loc, 'madras')) return 6;
        if (str_contains($loc, 'pune')) return 7;
        if (str_contains($loc, 'mumbai') || str_contains($loc, 'bombay') || str_contains($loc, 'navi mumbai')) return 8;

        return 1; // Default to Bengaluru
    }

    /**
     * Maps experience category and years to ExpLevelCat ID.
     */
    private function getExpLevelId(string $expCategory, ?int $years = null): int
    {
        if ($expCategory === 'intern') return 1; // Internships
        if ($expCategory === 'fresher') {
            if ($years === 1) return 3; // 0-1 Years
            return 2; // Freshers
        }
        if ($expCategory === 'mid-level') {
            if ($years !== null && $years <= 3) return 4; // 1-3 Years
            return 5; // 3-5 Years
        }
        if ($expCategory === 'experienced') {
            return 6; // Senior Roles (5+ Years)
        }
        return 2; // Default to Freshers
    }

    /**
     * Checks if a job description indicates 4+ years of experience required.
     * Parses ALL experience range mentions and rejects if the UPPER BOUND exceeds 3 years.
     * 
     * Examples that PASS (allowed):
     *   - "0-2 years" → upper=2 ✓
     *   - "1-3 years of experience" → upper=3 ✓
     *   - "0+ years" → upper=0 ✓
     *   
     * Examples that FAIL (rejected):
     *   - "2-7 years" → upper=7 ✗
     *   - "3-5 years" → upper=5 ✗  
     *   - "4+ years" → upper=4 ✗
     *   - "5 years of experience" → upper=5 ✗
     *   - "minimum 4 years" → upper=4 ✗
     */
    private function requiresHighExperience(string $contentHtml): bool
    {
        $text = strtolower(strip_tags($contentHtml));

        // ── Pattern 1: Range format "X-Y years" or "X to Y years" ──
        // Captures: "2-7 years", "3 to 5 years", "2 - 4 yrs experience"
        if (preg_match_all('/(\d+)\s*(?:-|to)\s*(\d+)\s*\+?\s*(?:years?|yrs?)\b/i', $text, $matches)) {
            foreach ($matches[2] as $upperBound) {
                if ((int) $upperBound > 3) {
                    return true;
                }
            }
        }

        // ── Pattern 2: Single number "X+ years" or "X years of experience" ──
        // Captures: "4+ years", "5 years of experience", "7 yrs"
        if (preg_match_all('/\b(\d+)\s*\+?\s*(?:years?|yrs?)\s+(?:of\s+)?(?:relevant\s+|professional\s+|hands[- ]on\s+|industry\s+)?experience\b/i', $text, $matches)) {
            foreach ($matches[1] as $years) {
                if ((int) $years > 3) {
                    return true;
                }
            }
        }

        // ── Pattern 3: "minimum X years" or "at least X years" ──
        if (preg_match_all('/(?:minimum|at\s+least|min\.?)\s*(\d+)\s*\+?\s*(?:years?|yrs?)/i', $text, $matches)) {
            foreach ($matches[1] as $years) {
                if ((int) $years > 3) {
                    return true;
                }
            }
        }

        // ── Pattern 4: "experience of X+ years" or "experience: X years" ──
        if (preg_match_all('/experience[\s:]*(?:of\s*)?(\d+)\s*\+?\s*(?:-|to)?\s*(?:\d+)?\s*(?:years?|yrs?)/i', $text, $matches)) {
            foreach ($matches[1] as $years) {
                if ((int) $years > 3) {
                    return true;
                }
            }
        }

        // ── Pattern 5: Seniority phrases that strongly imply 5+ years ──
        $seniorPhrases = [
            'extensive experience',
            'seasoned professional',
            'deep expertise',
            'proven track record',
            'significant experience',
            'substantial experience',
        ];
        foreach ($seniorPhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Maps the companyType string from Groq to the corresponding integer ID in jobsbcompanytype table.
     */
    private function getCompanyTypeId(?string $companyType): int
    {
        if (!$companyType) return 1;
        
        $type = strtolower($companyType);
        if (str_contains($type, 'product')) return 1;
        if (str_contains($type, 'service')) return 2;
        if (str_contains($type, 'startup')) return 3;
        if (str_contains($type, 'mnc')) return 4;
        if (str_contains($type, 'remote')) return 5;

        return 1;
    }
}
