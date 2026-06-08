<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Jobs;
use App\Services\GeminiParserService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapeAtsJobs extends Command
{
    protected $signature = 'jobs:scrape-ats';
    protected $description = 'Scrape early career jobs from Greenhouse/Lever and parse via Gemini API';

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

        $maxJobs = 1;
        $jobsAdded = 0;

        foreach ($greenhouseBoards as $board) {
            if ($jobsAdded >= $maxJobs) break;

            $this->info("Fetching Greenhouse jobs for: {$board}");
            
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
                if ($jobsAdded >= $maxJobs) break;

                $title = strtolower($job['title']);
                $location = $job['location']['name'] ?? 'Remote';
                
                // Early Career Filter (Strict Title Match)
                if (
                    Str::contains($title, ['senior', 'sr', 'staff', 'principal', 'director', 'lead', 'manager', 'head', 'architect', 'expert'])
                ) {
                    continue;
                }

                if (
                    !Str::contains($title, ['intern', 'fresher', 'junior', 'jr', 'graduate', 'associate', 'early', 'entry']) 
                ) {
                    continue; 
                }

                // India Location Filter
                if (!$this->isLocationInIndia($location)) {
                    continue;
                }

                // Technical/Software Roles Filter
                if (!$this->isTechnicalSoftwareRole($job['title'])) {
                    continue;
                }

                // Check if already exists by link
                if (Jobs::withoutGlobalScope('published')->where('joblink', $job['absolute_url'])->exists()) {
                    continue;
                }

                $this->info("Parsing matching job: {$job['title']}");

                // Fetch job details (Greenhouse requires hitting the specific job ID endpoint for the HTML description)
                try {
                    $detailRes = Http::timeout(15)->get("https://boards-api.greenhouse.io/v1/boards/{$board}/jobs/{$job['id']}");
                } catch (\Exception $e) {
                    $this->error("cURL/Connection error fetching Greenhouse job details for '{$job['title']}' on '{$board}': " . $e->getMessage());
                    continue;
                }

                if (!$detailRes->successful()) {
                    $this->warn("Greenhouse details API returned status {$detailRes->status()} for job ID {$job['id']}");
                    continue;
                }
                
                $contentHtml = $detailRes->json('content') ?? '';
                if (empty($contentHtml)) continue;

                // Strict Experience Regex Filter
                if ($this->requiresHighExperience($contentHtml)) {
                    $this->line("   -> Skipped: Description indicates 4+ years experience.");
                    continue;
                }

                $parsedFields = $parser->parseJobDescription($contentHtml);

                $isIntern = Str::contains($title, 'intern');
                if (isset($parsedFields['jobRoleCategory'])) {
                    $cat = strtolower($parsedFields['jobRoleCategory']);
                    if (str_contains($cat, 'intern')) $isIntern = true;
                    if (str_contains($cat, 'fresher')) $isIntern = false;
                }
                
                $jobtypeId = \App\Models\DomainCat::where('name', 'like', $isIntern ? '%Intern%' : '%Full Time%')->value('id');
                $locationId = \App\Models\LocationCat::where('name', 'like', '%' . $location . '%')->value('id');
                $expLevelId = \App\Models\ExpLevelCat::where('name', 'like', $isIntern ? '%Intern%' : '%Fresher%')->value('id');

                Jobs::create([
                    'title' => $parsedFields['seoTitle'] ?? $job['title'],
                    'role' => $job['title'], // default to title
                    'location' => $location,
                    'joblink' => $job['absolute_url'],
                    'description' => $parsedFields['jobDescription'] ?? strip_tags($contentHtml),
                    'rolesAndResponsibilities' => $parsedFields['rolesAndResponsibilities'] ?? null,
                    'requirements' => $parsedFields['requirements'] ?? null,
                    'niceToHave' => $parsedFields['niceToHave'] ?? null,
                    'eligibility' => $parsedFields['eligibility'] ?? null,
                    'pay' => $parsedFields['expectedSalary'] ?? null,
                    'batches' => $parsedFields['eligibleBatches'] ?? null,
                    'status' => 'draft',
                    'jobtype' => $jobtypeId,
                    'jobbycity' => $locationId,
                    'jobexplevel' => $expLevelId,
                    // other int fields are safely defaulted to null
                ]);

                $jobsAdded++;
                $this->info("Added Draft Job ({$jobsAdded}/{$maxJobs}) - {$job['title']}");
                
                // Sleep to respect rate limits
                sleep(2);
            }
        }

        // --- Lever API Scraping ---
        foreach ($leverBoards as $board) {
            if ($jobsAdded >= $maxJobs) break;

            $this->info("Fetching Lever jobs for: {$board}");
            
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
                if ($jobsAdded >= $maxJobs) break;

                $title = strtolower($job['text'] ?? '');
                $location = $job['categories']['location'] ?? 'Remote';
                
                // Early Career Filter
                if (Str::contains($title, ['senior', 'sr', 'staff', 'principal', 'director', 'lead', 'manager', 'head', 'architect', 'expert'])) {
                    continue;
                }
                if (!Str::contains($title, ['intern', 'fresher', 'junior', 'jr', 'graduate', 'associate', 'early', 'entry'])) {
                    continue; 
                }

                // India Location Filter
                if (!$this->isLocationInIndia($location)) {
                    continue;
                }

                // Technical/Software Roles Filter
                if (!$this->isTechnicalSoftwareRole($job['text'] ?? '')) {
                    continue;
                }

                if (Jobs::withoutGlobalScope('published')->where('joblink', $job['hostedUrl'])->exists()) {
                    continue;
                }

                $this->info("Parsing matching Lever job: {$job['text']}");

                $contentHtml = $job['descriptionPlain'] ?? $job['description'] ?? '';
                // Append Lever lists (requirements, etc.) to content HTML if available
                if (isset($job['lists']) && is_array($job['lists'])) {
                    foreach ($job['lists'] as $list) {
                        $contentList = $list['content'] ?? [''];
                        if (!is_array($contentList)) {
                            $contentList = [$contentList];
                        }
                        $contentHtml .= "<h3>" . ($list['text'] ?? '') . "</h3><ul><li>" . implode("</li><li>", $contentList) . "</li></ul>";
                    }
                }

                if (empty($contentHtml)) continue;

                // Strict Experience Regex Filter
                if ($this->requiresHighExperience($contentHtml)) {
                    $this->line("   -> Skipped: Description indicates 4+ years experience.");
                    continue;
                }

                $parsedFields = $parser->parseJobDescription($contentHtml);

                $isIntern = Str::contains($title, 'intern');
                if (isset($parsedFields['jobRoleCategory'])) {
                    $cat = strtolower($parsedFields['jobRoleCategory']);
                    if (str_contains($cat, 'intern')) $isIntern = true;
                    if (str_contains($cat, 'fresher')) $isIntern = false;
                }

                $jobtypeId = \App\Models\DomainCat::where('name', 'like', $isIntern ? '%Intern%' : '%Full Time%')->value('id');
                $locationId = \App\Models\LocationCat::where('name', 'like', '%' . $location . '%')->value('id');
                $expLevelId = \App\Models\ExpLevelCat::where('name', 'like', $isIntern ? '%Intern%' : '%Fresher%')->value('id');

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
                    'pay' => $parsedFields['expectedSalary'] ?? null,
                    'batches' => $parsedFields['eligibleBatches'] ?? null,
                    'status' => 'draft',
                    'jobtype' => $jobtypeId,
                    'jobbycity' => $locationId,
                    'jobexplevel' => $expLevelId,
                ]);

                $jobsAdded++;
                $this->info("Added Draft Job ({$jobsAdded}/{$maxJobs}) - {$job['text']}");
                
                sleep(2);
            }
        }

        $this->info("Scraping complete. Added {$jobsAdded} new jobs as draft.");
        return Command::SUCCESS;
    }

    private function isTechnicalSoftwareRole(string $title): bool
    {
        $title = strtolower($title);

        // 1. Exclude Senior / Manager / Lead / Director roles
        if (preg_match('/\b(senior|sr|staff|principal|director|lead|manager|head|vp|president|expert|architect)\b/i', $title)) {
            return false;
        }

        // 2. Exclude Non-technical roles (Blacklist - EXPANDED)
        $blacklistRegex = '/\b(support|helpdesk|help desk|customer|sales|marketing|hr|human resources|recruiter|recruiting|talent|operations|ops|designer|design|finance|accounting|accountant|legal|writer|content|admin|administrator|coordinator|executive|assistant|business analyst|product manager|product owner|consultant|strategist|compliance|media|copywriter|editor|purchasing|buyer|logistics|supply chain|growth)\b/i';
        if (preg_match($blacklistRegex, $title)) {
            return false;
        }

        // 3. Include only Technical / Software role keywords (Whitelist - STRICT)
        $whitelistRegex = '/\b(software|developer|engineer|sde|programmer|coder|frontend|backend|fullstack|full-stack|devops|cloud|qa|test|testing|sdet|data|analytics|machine learning|ml|ai|artificial intelligence|systems|network|security|cybersecurity|database|db|infrastructure|mobile|android|ios|sre)\b/i';
        if (preg_match($whitelistRegex, $title)) {
            return true;
        }

        return false;
    }

    private function isLocationInIndia(string $location): bool
    {
        $location = strtolower($location);
        
        $indiaKeywords = [
            'india', 'bangalore', 'bengaluru', 'hyderabad', 'pune', 'mumbai', 
            'delhi', 'new delhi', 'noida', 'gurugram', 'gurgaon', 'chennai', 
            'kolkata', 'ahmedabad', 'thiruvananthapuram', 'trivandrum', 'kochi',
            'indore', 'chandigarh', 'jaipur'
        ];

        foreach ($indiaKeywords as $keyword) {
            if (str_contains($location, $keyword)) {
                return true;
            }
        }
        
        // Special case: if it explicitly says Remote India
        if (str_contains($location, 'remote') && str_contains($location, 'india')) {
            return true;
        }

        return false;
    }

    private function requiresHighExperience(string $contentHtml): bool
    {
        $text = strtolower(strip_tags($contentHtml));

        // Match "X years of experience" where X >= 4
        $pattern1 = '/\b([4-9]|[1-9]\d{1,2})\+?\s*(?:-|to)?\s*(?:\d+)?\s*(?:years?|yrs?)\s+(?:of\s+)?experience\b/i';
        
        // Match "Experience: X+ years" where X >= 4
        $pattern2 = '/\bexperience[\s:]*(?:of\s*)?([4-9]|[1-9]\d{1,2})\+?\s*(?:-|to)?\s*(?:\d+)?\s*(?:years?|yrs?)\b/i';

        if (preg_match($pattern1, $text) || preg_match($pattern2, $text)) {
            return true;
        }

        return false;
    }
}
