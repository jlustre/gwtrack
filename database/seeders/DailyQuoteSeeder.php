<?php

namespace Database\Seeders;

use App\Models\DailyQuote;
use Illuminate\Database\Seeder;

class DailyQuoteSeeder extends Seeder
{
    public function run(): void
    {
        $quotes =         [
          0 => 
          [
            'quote' => 'Leadership is not about being in charge. It is about taking care of those in your charge.',
            'author' => 'Simon Sinek',
            'is_active' => true,
            'sort_order' => 1,
          ],
          1 => 
          [
            'quote' => 'Success is the sum of small efforts, repeated day in and day out.',
            'author' => 'Robert Collier',
            'is_active' => true,
            'sort_order' => 2,
          ],
          2 => 
          [
            'quote' => 'The best way to predict the future is to create it.',
            'author' => 'Peter Drucker',
            'is_active' => true,
            'sort_order' => 3,
          ],
          3 => 
          [
            'quote' => 'Discipline is the bridge between goals and accomplishment.',
            'author' => 'Jim Rohn',
            'is_active' => true,
            'sort_order' => 4,
          ],
          4 => 
          [
            'quote' => 'Your network is your net worth, but your service is your legacy.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 5,
          ],
          5 => 
          [
            'quote' => 'Consistency beats intensity when you are building a lasting career.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 6,
          ],
          6 => 
          [
            'quote' => 'Every conversation with a prospect is an opportunity to serve with integrity.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 7,
          ],
          7 => 
          [
            'quote' => 'Mentorship multiplies growth — invest in others and your impact compounds.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 8,
          ],
          8 => 
          [
            'quote' => 'Small daily improvements lead to staggering long-term results.',
            'author' => 'Robin Sharma',
            'is_active' => true,
            'sort_order' => 9,
          ],
          9 => 
          [
            'quote' => 'Excellence is not an act, but a habit.',
            'author' => 'Aristotle',
            'is_active' => true,
            'sort_order' => 10,
          ],
          10 => 
          [
            'quote' => 'The only way to do great work is to love what you do.',
            'author' => 'Steve Jobs',
            'is_active' => true,
            'sort_order' => 11,
          ],
          11 => 
          [
            'quote' => 'Opportunities don\'t happen. You create them.',
            'author' => 'Chris Grosser',
            'is_active' => true,
            'sort_order' => 12,
          ],
          12 => 
          [
            'quote' => 'Do not wait to strike till the iron is hot; but make it hot by striking.',
            'author' => 'William Butler Yeats',
            'is_active' => true,
            'sort_order' => 13,
          ],
          13 => 
          [
            'quote' => 'What gets measured gets managed.',
            'author' => 'Peter Drucker',
            'is_active' => true,
            'sort_order' => 14,
          ],
          14 => 
          [
            'quote' => 'People don\'t care how much you know until they know how much you care.',
            'author' => 'Theodore Roosevelt',
            'is_active' => true,
            'sort_order' => 15,
          ],
          15 => 
          [
            'quote' => 'A goal without a plan is just a wish.',
            'author' => 'Antoine de Saint-Exupéry',
            'is_active' => true,
            'sort_order' => 16,
          ],
          16 => 
          [
            'quote' => 'The secret of getting ahead is getting started.',
            'author' => 'Mark Twain',
            'is_active' => true,
            'sort_order' => 17,
          ],
          17 => 
          [
            'quote' => 'Quality is not an act, it is a habit.',
            'author' => 'Aristotle',
            'is_active' => true,
            'sort_order' => 18,
          ],
          18 => 
          [
            'quote' => 'Courage is being scared to death and saddling up anyway.',
            'author' => 'John Wayne',
            'is_active' => true,
            'sort_order' => 19,
          ],
          19 => 
          [
            'quote' => 'You miss 100% of the shots you don\'t take.',
            'author' => 'Wayne Gretzky',
            'is_active' => true,
            'sort_order' => 20,
          ],
          20 => 
          [
            'quote' => 'Act as if what you do makes a difference. It does.',
            'author' => 'William James',
            'is_active' => true,
            'sort_order' => 21,
          ],
          21 => 
          [
            'quote' => 'It always seems impossible until it\'s done.',
            'author' => 'Nelson Mandela',
            'is_active' => true,
            'sort_order' => 22,
          ],
          22 => 
          [
            'quote' => 'Don\'t watch the clock; do what it does. Keep going.',
            'author' => 'Sam Levenson',
            'is_active' => true,
            'sort_order' => 23,
          ],
          23 => 
          [
            'quote' => 'The harder you work for something, the greater you\'ll feel when you achieve it.',
            'author' => 'Anonymous',
            'is_active' => true,
            'sort_order' => 24,
          ],
          24 => 
          [
            'quote' => 'Dream big. Start small. Act now.',
            'author' => 'Robin Sharma',
            'is_active' => true,
            'sort_order' => 25,
          ],
          25 => 
          [
            'quote' => 'Your attitude, not your aptitude, will determine your altitude.',
            'author' => 'Zig Ziglar',
            'is_active' => true,
            'sort_order' => 26,
          ],
          26 => 
          [
            'quote' => 'If you are not willing to risk the unusual, you will have to settle for the ordinary.',
            'author' => 'Jim Rohn',
            'is_active' => true,
            'sort_order' => 27,
          ],
          27 => 
          [
            'quote' => 'Success usually comes to those who are too busy to be looking for it.',
            'author' => 'Henry David Thoreau',
            'is_active' => true,
            'sort_order' => 28,
          ],
          28 => 
          [
            'quote' => 'The way to get started is to quit talking and begin doing.',
            'author' => 'Walt Disney',
            'is_active' => true,
            'sort_order' => 29,
          ],
          29 => 
          [
            'quote' => 'Don\'t be afraid to give up the good to go for the great.',
            'author' => 'John D. Rockefeller',
            'is_active' => true,
            'sort_order' => 30,
          ],
          30 => 
          [
            'quote' => 'I find that the harder I work, the more luck I seem to have.',
            'author' => 'Thomas Jefferson',
            'is_active' => true,
            'sort_order' => 31,
          ],
          31 => 
          [
            'quote' => 'Whether you think you can or you think you can\'t, you\'re right.',
            'author' => 'Henry Ford',
            'is_active' => true,
            'sort_order' => 32,
          ],
          32 => 
          [
            'quote' => 'The future belongs to those who believe in the beauty of their dreams.',
            'author' => 'Eleanor Roosevelt',
            'is_active' => true,
            'sort_order' => 33,
          ],
          33 => 
          [
            'quote' => 'It is during our darkest moments that we must focus to see the light.',
            'author' => 'Aristotle',
            'is_active' => true,
            'sort_order' => 34,
          ],
          34 => 
          [
            'quote' => 'Believe you can and you\'re halfway there.',
            'author' => 'Theodore Roosevelt',
            'is_active' => true,
            'sort_order' => 35,
          ],
          35 => 
          [
            'quote' => 'Everything you\'ve ever wanted is on the other side of fear.',
            'author' => 'George Addair',
            'is_active' => true,
            'sort_order' => 36,
          ],
          36 => 
          [
            'quote' => 'Hardships often prepare ordinary people for an extraordinary destiny.',
            'author' => 'C.S. Lewis',
            'is_active' => true,
            'sort_order' => 37,
          ],
          37 => 
          [
            'quote' => 'Start where you are. Use what you have. Do what you can.',
            'author' => 'Arthur Ashe',
            'is_active' => true,
            'sort_order' => 38,
          ],
          38 => 
          [
            'quote' => 'What we fear doing most is usually what we most need to do.',
            'author' => 'Tim Ferriss',
            'is_active' => true,
            'sort_order' => 39,
          ],
          39 => 
          [
            'quote' => 'Motivation gets you going, but discipline keeps you growing.',
            'author' => 'John C. Maxwell',
            'is_active' => true,
            'sort_order' => 40,
          ],
          40 => 
          [
            'quote' => 'Leaders become great not because of their power, but because of their ability to empower others.',
            'author' => 'John C. Maxwell',
            'is_active' => true,
            'sort_order' => 41,
          ],
          41 => 
          [
            'quote' => 'Trust is the glue of life. It\'s the most essential ingredient in effective communication.',
            'author' => 'Stephen Covey',
            'is_active' => true,
            'sort_order' => 42,
          ],
          42 => 
          [
            'quote' => 'Seek first to understand, then to be understood.',
            'author' => 'Stephen Covey',
            'is_active' => true,
            'sort_order' => 43,
          ],
          43 => 
          [
            'quote' => 'Begin with the end in mind.',
            'author' => 'Stephen Covey',
            'is_active' => true,
            'sort_order' => 44,
          ],
          44 => 
          [
            'quote' => 'We are what we repeatedly do. Excellence, then, is not an act, but a habit.',
            'author' => 'Will Durant',
            'is_active' => true,
            'sort_order' => 45,
          ],
          45 => 
          [
            'quote' => 'An investment in knowledge pays the best interest.',
            'author' => 'Benjamin Franklin',
            'is_active' => true,
            'sort_order' => 46,
          ],
          46 => 
          [
            'quote' => 'Tell me and I forget. Teach me and I remember. Involve me and I learn.',
            'author' => 'Benjamin Franklin',
            'is_active' => true,
            'sort_order' => 47,
          ],
          47 => 
          [
            'quote' => 'Well done is better than well said.',
            'author' => 'Benjamin Franklin',
            'is_active' => true,
            'sort_order' => 48,
          ],
          48 => 
          [
            'quote' => 'Either you run the day or the day runs you.',
            'author' => 'Jim Rohn',
            'is_active' => true,
            'sort_order' => 49,
          ],
          49 => 
          [
            'quote' => 'Formal education will make you a living; self-education will make you a fortune.',
            'author' => 'Jim Rohn',
            'is_active' => true,
            'sort_order' => 50,
          ],
          50 => 
          [
            'quote' => 'Don\'t wish it were easier. Wish you were better.',
            'author' => 'Jim Rohn',
            'is_active' => true,
            'sort_order' => 51,
          ],
          51 => 
          [
            'quote' => 'If you don\'t design your own life plan, chances are you\'ll fall into someone else\'s.',
            'author' => 'Jim Rohn',
            'is_active' => true,
            'sort_order' => 52,
          ],
          52 => 
          [
            'quote' => 'Activity is not achievement.',
            'author' => 'John Wooden',
            'is_active' => true,
            'sort_order' => 53,
          ],
          53 => 
          [
            'quote' => 'Be quick, but don\'t hurry.',
            'author' => 'John Wooden',
            'is_active' => true,
            'sort_order' => 54,
          ],
          54 => 
          [
            'quote' => 'Failing to prepare is preparing to fail.',
            'author' => 'John Wooden',
            'is_active' => true,
            'sort_order' => 55,
          ],
          55 => 
          [
            'quote' => 'Make each day your masterpiece.',
            'author' => 'John Wooden',
            'is_active' => true,
            'sort_order' => 56,
          ],
          56 => 
          [
            'quote' => 'The only place where success comes before work is in the dictionary.',
            'author' => 'Vidal Sassoon',
            'is_active' => true,
            'sort_order' => 57,
          ],
          57 => 
          [
            'quote' => 'Action is the foundational key to all success.',
            'author' => 'Pablo Picasso',
            'is_active' => true,
            'sort_order' => 58,
          ],
          58 => 
          [
            'quote' => 'Do what you can, with what you have, where you are.',
            'author' => 'Theodore Roosevelt',
            'is_active' => true,
            'sort_order' => 59,
          ],
          59 => 
          [
            'quote' => 'Far and away the best prize that life has to offer is the chance to work hard at work worth doing.',
            'author' => 'Theodore Roosevelt',
            'is_active' => true,
            'sort_order' => 60,
          ],
          60 => 
          [
            'quote' => 'Keep your eyes on the stars, and your feet on the ground.',
            'author' => 'Theodore Roosevelt',
            'is_active' => true,
            'sort_order' => 61,
          ],
          61 => 
          [
            'quote' => 'A person who never made a mistake never tried anything new.',
            'author' => 'Albert Einstein',
            'is_active' => true,
            'sort_order' => 62,
          ],
          62 => 
          [
            'quote' => 'In the middle of every difficulty lies opportunity.',
            'author' => 'Albert Einstein',
            'is_active' => true,
            'sort_order' => 63,
          ],
          63 => 
          [
            'quote' => 'Strive not to be a success, but rather to be of value.',
            'author' => 'Albert Einstein',
            'is_active' => true,
            'sort_order' => 64,
          ],
          64 => 
          [
            'quote' => 'Life is 10% what happens to us and 90% how we react to it.',
            'author' => 'Charles R. Swindoll',
            'is_active' => true,
            'sort_order' => 65,
          ],
          65 => 
          [
            'quote' => 'The difference between ordinary and extraordinary is that little extra.',
            'author' => 'Jimmy Johnson',
            'is_active' => true,
            'sort_order' => 66,
          ],
          66 => 
          [
            'quote' => 'Success is walking from failure to failure with no loss of enthusiasm.',
            'author' => 'Winston Churchill',
            'is_active' => true,
            'sort_order' => 67,
          ],
          67 => 
          [
            'quote' => 'Continuous effort — not strength or intelligence — is the key to unlocking our potential.',
            'author' => 'Winston Churchill',
            'is_active' => true,
            'sort_order' => 68,
          ],
          68 => 
          [
            'quote' => 'To improve is to change; to be perfect is to change often.',
            'author' => 'Winston Churchill',
            'is_active' => true,
            'sort_order' => 69,
          ],
          69 => 
          [
            'quote' => 'Go as far as you can see; when you get there, you\'ll be able to see further.',
            'author' => 'Thomas Carlyle',
            'is_active' => true,
            'sort_order' => 70,
          ],
          70 => 
          [
            'quote' => 'What you get by achieving your goals is not as important as what you become by achieving your goals.',
            'author' => 'Zig Ziglar',
            'is_active' => true,
            'sort_order' => 71,
          ],
          71 => 
          [
            'quote' => 'You don\'t have to be great to start, but you have to start to be great.',
            'author' => 'Zig Ziglar',
            'is_active' => true,
            'sort_order' => 72,
          ],
          72 => 
          [
            'quote' => 'If people like you, they\'ll listen to you, but if they trust you, they\'ll do business with you.',
            'author' => 'Zig Ziglar',
            'is_active' => true,
            'sort_order' => 73,
          ],
          73 => 
          [
            'quote' => 'Stop selling. Start helping.',
            'author' => 'Zig Ziglar',
            'is_active' => true,
            'sort_order' => 74,
          ],
          74 => 
          [
            'quote' => 'Prospecting is the heartbeat of a growing practice.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 75,
          ],
          75 => 
          [
            'quote' => 'A licensed associate with a full calendar is an unstoppable force.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 76,
          ],
          76 => 
          [
            'quote' => 'Protect families with excellence, and production will follow.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 77,
          ],
          77 => 
          [
            'quote' => 'Your pipeline is a reflection of your daily disciplines.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 78,
          ],
          78 => 
          [
            'quote' => 'Recruit with vision, train with patience, and lead with example.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 79,
          ],
          79 => 
          [
            'quote' => 'The best CFMs make their trainees feel capable before they feel comfortable.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 80,
          ],
          80 => 
          [
            'quote' => 'Follow-up is where good intentions become closed cases.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 81,
          ],
          81 => 
          [
            'quote' => 'Integrity turns a transaction into a lifelong client relationship.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 82,
          ],
          82 => 
          [
            'quote' => 'Celebrate progress, then raise the standard.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 83,
          ],
          83 => 
          [
            'quote' => 'Your morning activity sets the tone for your evening results.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 84,
          ],
          84 => 
          [
            'quote' => 'Build people, and they will build the business.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 85,
          ],
          85 => 
          [
            'quote' => 'Clarity creates confidence. Confidence creates action.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 86,
          ],
          86 => 
          [
            'quote' => 'A written goal becomes a committed target.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 87,
          ],
          87 => 
          [
            'quote' => 'When in doubt, make one more meaningful contact.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 88,
          ],
          88 => 
          [
            'quote' => 'Master the fundamentals before chasing the shortcuts.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 89,
          ],
          89 => 
          [
            'quote' => 'Service first. Sales second. Legacy always.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 90,
          ],
          90 => 
          [
            'quote' => 'Your calendar should reflect your priorities, not your interruptions.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 91,
          ],
          91 => 
          [
            'quote' => 'Ask better questions and you will uncover better opportunities.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 92,
          ],
          92 => 
          [
            'quote' => 'Momentum is built in private and revealed in public.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 93,
          ],
          93 => 
          [
            'quote' => 'Be the mentor you needed when you started.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 94,
          ],
          94 => 
          [
            'quote' => 'Today\'s unfinished follow-ups become tomorrow\'s lost opportunities.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 95,
          ],
          95 => 
          [
            'quote' => 'Great teams are built one intentional conversation at a time.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 96,
          ],
          96 => 
          [
            'quote' => 'Stay coachable longer than you stay comfortable.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 97,
          ],
          97 => 
          [
            'quote' => 'Results love consistency more than inspiration.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 98,
          ],
          98 => 
          [
            'quote' => 'Own your numbers. Own your narrative. Own your next step.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 99,
          ],
          99 => 
          [
            'quote' => 'Progress compounds when you show up prepared.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 100,
          ],
          100 => 
          [
            'quote' => 'A grateful associate attracts loyal clients and eager recruits.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 101,
          ],
          101 => 
          [
            'quote' => 'Don\'t just chase production — build a transferable system.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 102,
          ],
          102 => 
          [
            'quote' => 'The best close starts with a sincere discovery.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 103,
          ],
          103 => 
          [
            'quote' => 'Lead yourself first. Then lead your team.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 104,
          ],
          104 => 
          [
            'quote' => 'Energy without direction is noise. Direction without energy is delay.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 105,
          ],
          105 => 
          [
            'quote' => 'Finish the day better than you started it.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 106,
          ],
          106 => 
          [
            'quote' => 'Your reputation is the quietest and loudest marketing you will ever do.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 107,
          ],
          107 => 
          [
            'quote' => 'Train until the right response becomes automatic.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 108,
          ],
          108 => 
          [
            'quote' => 'Courage is contagious — so is commitment.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 109,
          ],
          109 => 
          [
            'quote' => 'Make excellence your minimum standard.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 110,
          ],
          110 => 
          [
            'quote' => 'The appointment you schedule today protects a family tomorrow.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 111,
          ],
          111 => 
          [
            'quote' => 'Growth requires discomfort. Comfort requires excuses.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 112,
          ],
          112 => 
          [
            'quote' => 'Keep score honestly and improve relentlessly.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 113,
          ],
          113 => 
          [
            'quote' => 'A strong week is just five strong days stacked together.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 114,
          ],
          114 => 
          [
            'quote' => 'Listen more than you pitch. Serve more than you sell.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 115,
          ],
          115 => 
          [
            'quote' => 'Your future team is watching how you handle today.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 116,
          ],
          116 => 
          [
            'quote' => 'Turn rejection into refinement, not retreat.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 117,
          ],
          117 => 
          [
            'quote' => 'Preparation turns nervous energy into professional presence.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 118,
          ],
          118 => 
          [
            'quote' => 'If it matters, put it on the calendar.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 119,
          ],
          119 => 
          [
            'quote' => 'Win the morning contacts and the afternoon takes care of itself.',
            'author' => 'GW Leadership',
            'is_active' => true,
            'sort_order' => 120,
          ],
        ];

        foreach ($quotes as $index => $entry) {
            DailyQuote::query()->withTrashed()->updateOrCreate(
                ['quote' => $entry['quote']],
                [
                    'author' => $entry['author'] ?? null,
                    'is_active' => (bool) ($entry['is_active'] ?? true),
                    'sort_order' => (int) ($entry['sort_order'] ?? ($index + 1)),
                    'deleted_at' => null,
                ],
            );
        }
    }
}
