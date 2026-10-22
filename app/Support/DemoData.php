<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Item;
use App\Models\Tab;

class DemoData
{
    public static function strategy(): array
    {
        return [
            'do' => [
                'Precise, measured, editorial',
                'One idea per post. No hedging.',
                'Name the problem before the solution',
                'Operational specificity — not hustle culture',
                'Direct second person: "You trained this."',
            ],
            'dont' => [
                'Motivational filler or inspirational fluff',
                'List posts for the sake of reach',
                'Qualify every statement',
                'Hustle praise or grind culture',
                'Passive voice or corporate hedging',
            ],
            'pillars' => [
                ['Dependency', 'The founder in every decision. The bottleneck they built — and how to dismantle it.'],
                ['Structure', 'Workarounds become the process. Org charts that move friction, not remove it.'],
                ['Clarity / Rework', 'The invisible cost of unclear briefs. When rework becomes the standard.'],
                ['People / Retention', 'High performers leave dysfunction, not salaries. The operations problem HR calls culture.'],
                ['Scaling', 'Growth targets set in boardrooms. Operating models built for the current size.'],
                ['Book', 'Running the Business, Not the Chaos — the thread connecting all content.'],
            ],
            'arc_title' => 'Campaign Arc — May to June 2025',
            'arc' => [
                ['Week 1', 'Hook & Provocation', 'Name the problem. No solution yet. Make the founder feel seen and uncomfortable.'],
                ['Week 2', 'Insight & Reframe', 'Reveal the real cause. The dependency loop. The system they trained.'],
                ['Week 3', 'Solution & CTA', 'Structure as the answer. The book as the entry point.'],
                ['Week 4', 'Wrap-up & Proof', 'Close the arc with proof points and a clear next step for the reader.'],
            ],
            'mix' => [
                ['Campaign Posts', 'May + June', 10],
                ['Book Promos', 'Static + Carousel', 5],
                ['Casual Reels', 'Bridge Week', 5],
                ['Podcast Episodes', 'Director Series', 15],
            ],
        ];
    }

    /** An empty workspace: a blank strategy tab and a three-column campaign grid. */
    public static function blank(Account $account): void
    {
        Tab::create(['account_id' => $account->id, 'label' => 'Strategy', 'kind' => 'strategy', 'position' => 0, 'meta' => [
            'title' => 'Strategy', 'do' => [], 'dont' => [], 'pillars' => [], 'arc_title' => 'Campaign arc', 'arc' => [], 'mix' => [],
        ]]);
        Tab::create(['account_id' => $account->id, 'label' => 'Campaign Grid', 'kind' => 'grid', 'position' => 1, 'meta' => [
            'overview' => true, 'title' => 'Campaign Grid — Full Overview', 'subtitle' => 'Plan each post across three columns',
            'columns' => ['Column one', 'Column two', 'Column three'],
        ]]);
    }

    /** In-memory sample data (never stored) so templates can be previewed before a workspace has content. */
    public static function sampleTabs(): array
    {
        $statuses = ['live', 'done', 'ready', 'ready', 'todo', 'todo', 'todo', 'done'];
        $mk = function (string $label, string $kind, int $n, string $prefix) use ($statuses) {
            $items = [];
            for ($i = 0; $i < $n; $i++) {
                $items[] = ['status' => $statuses[$i % count($statuses)], 'locked' => false, 'data' => [
                    'label' => sprintf('%s %02d', $prefix, $i + 1), 'day' => 'Day '.($i * 2 + 1).' · Week '.(intdiv($i, 3) + 1),
                    'hook' => ['HOOK', 'INSIGHT', 'PROOF', 'CTA'][$i % 4], 'caption' => 'Sample caption for post '.($i + 1).' — replace with your own copy.',
                    'platforms' => [['IG', 'LI', 'FB'], ['IG', 'LI'], ['LI']][$i % 3], 'time' => ['8:00 AM', '1:00 PM', '6:00 PM'][$i % 3],
                    'notes' => $i % 4 === 0 ? 'Client asked for a warmer tone.' : '', 'visual' => 'Clean layout, brand colours.',
                    'duration' => '10–12 min', 'format' => 'CASUAL CONVO',
                ]];
            }

            return ['label' => $label, 'kind' => $kind, 'meta' => ['title' => $label], 'items' => $items];
        };

        return [
            ['label' => 'Strategy', 'kind' => 'strategy', 'meta' => self::strategy(), 'items' => []],
            $mk('May', 'posts', 8, 'POST'),
            $mk('June', 'posts', 6, 'POST'),
            $mk('Reels', 'reels', 5, 'REEL'),
        ];
    }

    public static function seed(Account $account): void
    {
        $account->tabs()->each(fn ($t) => $t->delete());
        $account->notes()->delete();

        $pos = 0;
        $tab = fn (string $label, string $kind, array $meta = []) => Tab::create([
            'account_id' => $account->id, 'label' => $label, 'kind' => $kind, 'position' => $pos++, 'meta' => $meta,
        ]);

        $tab('Strategy', 'strategy', self::strategy());

        // ── Campaign grid (Chaos / System / Probably)
        $grid = $tab('Campaign Grid', 'grid', [
            'overview' => true,
            'title' => 'Campaign Grid — Full Overview',
            'subtitle' => '15 posts across Chaos / System / Probably columns',
            'columns' => ['The Chaos', 'The System', 'What Probably Happened'],
        ]);
        $rows = [
            ['You\'re in every decision.', 'That\'s not leadership. That\'s dependency.', 'If everything needs you, nothing is built.',
                'Close-up workspace.', 'Clear central structure.', 'Single object isolated.', ['IG', 'LI', 'FB'], '8:00 AM'],
            ['You hired to move faster. You didn\'t.', 'Headcount isn\'t the fix. Process is.', 'More people, same bottleneck.',
                'More people, same bottleneck.', 'Clear handover structure', 'Workspace lighter', ['IG', 'LI'], '7:30 AM'],
            ['You\'re six months in and Q1 goals are still in progress.', 'Strategy without a cadence is a wish.', 'The deck was perfect. The execution didn\'t exist.',
                'Calendar with empty review slots.', 'Weekly operating rhythm on one page.', 'Slide deck beside blank task board.', ['IG', 'LI', 'FB'], '8:00 AM'],
            ['Everyone is busy. Nothing is shipping.', 'Busy is a symptom. Unclear ownership is the cause.', 'Three people thought someone else had it.',
                'Crowded desk, no finished work.', 'Owner name beside each deliverable.', 'Three hands, one task.', ['IG', 'LI'], '12:30 PM'],
            ['Your best people just stopped suggesting ideas.', 'Silence isn\'t agreement. It\'s resignation.', 'They tried twice. Nothing changed.',
                'Empty meeting room, one chair pulled out.', 'A visible feedback loop.', 'Suggestion box, dust on the lid.', ['IG', 'LI', 'FB'], '6:00 PM'],
        ];
        foreach ($rows as $i => $r) {
            foreach ([0, 1, 2] as $c) {
                $names = ['CHAOS', 'SYSTEM', 'PROBABLY'];
                Item::create([
                    'tab_id' => $grid->id, 'position' => $i, 'col' => $c,
                    'status' => $i === 0 ? 'live' : 'todo',
                    'data' => [
                        'label' => sprintf('POST %02d · %s', $i + 1, $names[$c]),
                        'day' => 'Day '.($i * 2 + 1).' · Week '.(intdiv($i, 2) + 1),
                        'caption' => $r[$c],
                        'visual' => $r[3 + $c],
                        'platforms' => $r[6],
                        'time' => $r[7],
                        'notes' => '',
                    ],
                ]);
            }
        }

        // ── May 2025
        $may = $tab('May 2025', 'posts', ['title' => 'May 2025', 'subtitle' => '8 posts — Weeks 1 & 2']);
        $mayPosts = [
            ['HOOK', 'You\'re in every decision.', 'Close-up workspace. Multiple inputs centred.', ['IG', 'LI', 'FB'], '8:00 AM (GST)', 'Day 1 · Week 1'],
            ['PROVOCATION', 'That\'s not leadership. That\'s dependency.', 'Clear central structure.', ['IG', 'LI'], '1:30 PM (GST)', 'Day 3 · Week 1'],
            ['PAIN', 'If everything needs you, nothing is built.', 'Single central object isolated.', ['IG', 'LI', 'FB'], '8:00 AM (GST)', 'Day 5 · Week 1'],
            ['COST', 'Every decision you hold costs the team a day.', 'Calendar with stacked approvals.', ['IG', 'LI'], '12:00 PM (GST)', 'Day 7 · Week 1'],
            ['REFRAME', 'You didn\'t build a company. You built a queue.', 'Single-file line of documents.', ['IG', 'LI', 'FB'], '8:00 AM (GST)', 'Day 8 · Week 2'],
            ['INSIGHT', 'Teams don\'t wait for permission. They wait for clarity.', 'Open door, empty hallway.', ['LI'], '9:00 AM (GST)', 'Day 10 · Week 2'],
            ['SYSTEM', 'The fix isn\'t delegation. It\'s design.', 'Flow diagram, three nodes.', ['IG', 'LI'], '1:00 PM (GST)', 'Day 12 · Week 2'],
            ['BRIDGE', 'Next week: what a founder-free decision actually looks like.', 'Teaser frame, minimal type.', ['IG', 'LI', 'FB'], '6:00 PM (GST)', 'Day 14 · Week 2'],
        ];
        foreach ($mayPosts as $i => $p) {
            Item::create(['tab_id' => $may->id, 'position' => $i, 'status' => $i < 3 ? 'ready' : 'todo', 'data' => [
                'label' => sprintf('POST %02d', $i + 1), 'hook' => $p[0], 'caption' => $p[1], 'visual' => $p[2],
                'platforms' => $p[3], 'time' => $p[4], 'day' => $p[5], 'notes' => '',
            ]]);
        }
        // reflect the demo counters: only the very first piece is live/done
        Item::where('tab_id', $may->id)->update(['status' => 'todo']);
        Item::where('tab_id', $may->id)->where('position', 0)->update(['status' => 'done']);

        // ── June 2025
        $june = $tab('June 2025', 'posts', ['title' => 'June 2025', 'subtitle' => '6 posts — Weeks 3 & 4']);
        $junePosts = [
            ['SOLUTION', 'Structure is the answer.', 'Clean framework, one line.', ['IG', 'LI', 'FB'], '8:00 AM (GST)', 'Day 15 · Week 3'],
            ['PROOF', 'What changed when one founder stepped out of the loop.', 'Before / after split.', ['IG', 'LI'], '1:00 PM (GST)', 'Day 17 · Week 3'],
            ['CTA', 'The book is the entry point.', 'Book cover, soft shadow.', ['IG', 'LI', 'FB'], '8:00 AM (GST)', 'Day 19 · Week 3'],
            ['PROMO', 'Chapter one, free. Link in bio.', 'Static carousel, 5 frames.', ['IG', 'LI'], '12:00 PM (GST)', 'Day 22 · Week 4'],
            ['PROOF', 'Three operators. One pattern.', 'Triptych of portraits.', ['LI'], '9:00 AM (GST)', 'Day 24 · Week 4'],
            ['CLOSE', 'Running the business, not the chaos.', 'Single line on white.', ['IG', 'LI', 'FB'], '6:00 PM (GST)', 'Day 28 · Week 4'],
        ];
        foreach ($junePosts as $i => $p) {
            Item::create(['tab_id' => $june->id, 'position' => $i, 'status' => 'todo', 'data' => [
                'label' => sprintf('POST %02d', $i + 9), 'hook' => $p[0], 'caption' => $p[1], 'visual' => $p[2],
                'platforms' => $p[3], 'time' => $p[4], 'day' => $p[5], 'notes' => '',
            ]]);
        }

        // ── August (empty, as in the reference)
        $tab('August', 'grid', [
            'title' => 'August', 'subtitle' => 'Campaign posts',
            'columns' => ['The Chaos', 'The System', 'What Probably Happened'],
        ]);

        // ── October 2025 (long-form grid)
        $oct = $tab('October 2025', 'grid', [
            'title' => 'October 2025', 'subtitle' => 'Story-led posts — Chaos / System / Probably',
            'columns' => ['The Chaos', 'The System', 'What Probably Happened'],
        ]);
        $octRows = [
            [
                'The annual engagement survey came back at 74% positive. Leadership sent an all-staff note celebrating the result. Six months later the retention data arrived. Three senior people resigned in the same quarter. Two more are happening now. The 74% is still on the slide deck.',
                'Engagement surveys are backward-looking instruments. By the time the data exists and the report is written, the decision to leave has already been made — often months earlier. The leading indicators of retention are autonomy, psychological safety, and meaningful voice. These are rarely measured because they resist simple scoring.',
                'The organisation measured what was easy to measure. The survey predicted the outcome they needed, not the one that was coming. The structural pressure underneath never produced a real signal. Nobody asked the right question because the answer would have been felt. It didn\'t ask what they were afraid to hear.',
            ],
        ];
        $story = [
            'Poll: When did you last feel genuinely heard at work? — This week / Last month / Honestly can\'t remember',
            'Slide sequence: Annual survey X lagging / eNPS X lagging / Exit interview X lagging / What are you measuring before any of these?',
            'Open prompt: What\'s one signal your organisation missed — that in hindsight was obvious? Reply or DM. Keeping it anonymous.',
        ];
        foreach ($octRows as $i => $r) {
            foreach ([0, 1, 2] as $c) {
                $names = ['CHAOS', 'SYSTEM', 'PROBABLY'];
                Item::create(['tab_id' => $oct->id, 'position' => $i, 'col' => $c, 'status' => 'todo', 'data' => [
                    'label' => 'OCT 04 · '.$names[$c], 'caption' => $r[$c], 'story' => $story,
                    'visual' => '', 'platforms' => ['IG', 'LI', 'FB'], 'time' => '9:00 AM', 'notes' => '', 'day' => '',
                ]]);
            }
        }

        // ── Casual reels
        $reels = $tab('Casual Reels', 'reels', [
            'title' => 'Casual Reels — Bridge Week',
            'subtitle' => '5 reels · Casual Conversation + Voiceover · While director series is in production',
        ]);
        $reelRows = [
            ['CASUAL CONVO', 'Dependency', 'I asked a founder how many decisions go through her in a week. She said she didn\'t know. That was the answer.',
                'Camera on, slightly off-axis. No intro.', '"I was talking to a founder recently. I asked her — how many decisions go through you in a week? [pause] She said she wasn\'t sure. Probably a lot. [pause] That\'s the whole problem."',
                'One observation. Let the pause do the work.', 'Seated, slightly casual. Natural light.', 'Has your organisation ever measured how many decisions wait on one person?'],
            ['VOICEOVER', 'Structure', 'The org chart changed. The problem didn\'t.',
                'Text animates on screen. VO over clean visuals.', '"They restructured. Eighteen months of planning. New titles. [pause] The same inefficiencies are still there."',
                'VO — no face needed. Text overlays, clean.', 'Text on white bg. Brand colour for key words.', 'Afterwards, ask: what did the restructure actually remove?'],
            ['CASUAL CONVO', 'Clarity', 'Your team isn\'t slow. They\'re working from a bad brief.',
                'Direct to camera.', '"Most teams I walk into — they\'re not slow. The brief wasn\'t clear. The handover wasn\'t clean. That\'s a structure problem. And it\'s fixable."',
                'End on fixable.', 'Face to camera. Slightly more formal.', 'Has your organisation ever measured what rework costs annually?'],
            ['VOICEOVER', 'People / Retention', 'The best person on your team has already updated their CV.',
                'Slow push-in on an empty desk.', '"Not because of salary. Because of the system around them."',
                'Calm, low register.', 'Empty desk, warm side-light.', 'Who do you stand to lose first?'],
            ['CASUAL CONVO', 'Scaling', 'The plan was built for twenty people. You\'re at eighty.',
                'Walking shot, casual.', '"We keep running the company with an operating model designed for the size we used to be."',
                'Keep it conversational.', 'Corridor, daylight.', 'What did you outgrow without noticing?'],
        ];
        foreach ($reelRows as $i => $r) {
            Item::create(['tab_id' => $reels->id, 'position' => $i, 'status' => $i === 0 ? 'live' : 'todo', 'data' => [
                'label' => sprintf('REEL %02d', $i + 1), 'format' => $r[0], 'pillar' => $r[1], 'caption' => $r[2],
                'direction' => $r[3], 'script' => $r[4], 'delivery' => $r[5], 'visual' => $r[6], 'endcard' => $r[7], 'notes' => '',
            ]]);
        }

        // ── Podcasts
        $pods = $tab('Interview Podcasts', 'podcasts', [
            'title' => 'Interview Podcasts — Director Series',
            'subtitle' => '15 episodes · Production in progress',
        ]);
        $eps = [
            ['Dependency', 'You\'re the Bottleneck', 'When the founder is in every decision — what that actually costs.', '12–15 min'],
            ['Structure', 'Structure vs Hustle', 'Why working harder is the wrong answer.', '10–12 min'],
            ['Structure', 'The Org Chart Changed. Nothing Else Did.', 'Restructures that move friction instead of removing it.', '12–15 min'],
            ['Clarity/Rework', 'Rework is the Tax Nobody Measures', 'The invisible cost of unclear briefs and failed handovers.', '10–12 min'],
            ['Clarity/Rework', 'What Clarity Actually Means', 'Clarity isn\'t a communication skill. It\'s a design decision.', '10–12 min'],
            ['People/Retention', 'Your Best People Left for a Reason', 'High performers leave dysfunction, not salaries.', '10–12 min'],
            ['People/Retention', 'The Exit Interview is Too Late', 'Leading indicators of regretted attrition.', '12–15 min'],
            ['Scaling', 'Built for Twenty, Running at Eighty', 'Operating models that never scaled with the company.', '12–15 min'],
            ['Scaling', 'The Boardroom Target Problem', 'Growth numbers set without an operating plan.', '10–12 min'],
            ['Dependency', 'Delegation Is Not the Same as Design', 'Why handing off tasks fixes nothing.', '12–15 min'],
            ['Structure', 'Workarounds Become the Process', 'How temporary fixes harden into org structure.', '10–12 min'],
            ['Clarity/Rework', 'The Brief Nobody Read', 'Anatomy of a project that was doomed on day one.', '10–12 min'],
            ['People/Retention', 'Culture is an Operations Problem', 'What HR keeps being asked to fix.', '12–15 min'],
            ['Scaling', 'What Do You Stop Doing at 100?', 'The hardest decisions in growth.', '12–15 min'],
            ['Book', 'Running the Business, Not the Chaos', 'The book, the thesis, and what comes next.', '15–20 min'],
        ];
        foreach ($eps as $i => $e) {
            Item::create(['tab_id' => $pods->id, 'position' => $i, 'status' => 'todo', 'data' => [
                'label' => sprintf('EP %02d · %s', $i + 1, strtoupper($e[0])), 'pillar' => $e[0],
                'caption' => $e[1], 'hook' => $e[2], 'duration' => $e[3], 'notes' => '',
            ]]);
        }

        $account->notes()->create([
            'author' => 'Agency', 'role' => 'team',
            'body' => 'Strategy and content pillars are locked. Please review the Campaign Grid and leave comments on any post.',
        ]);
    }
}
