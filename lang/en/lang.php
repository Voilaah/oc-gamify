<?php

return [
    'plugin' => [
        'name' => 'Gamify',
        'description' => 'Gamification system for learning platforms',
    ],
    'missions' => [
        'voilaah_test' => [
            'name' => 'Course Explorer',
            'description' => 'Discover the world of learning by enrolling in courses',
            'completion_label' => 'Congratulations! You are now a true Course Explorer!',
            'levels' => [
                1 => [
                    'label' => 'Curious Learner',
                    'description' => 'Enroll in your first course',
                ],
                2 => [
                    'label' => 'Knowledge Seeker',
                    'description' => 'Enroll in 1 more course',
                ],
                3 => [
                    'label' => 'Dedicated Student',
                    'description' => 'Enroll in 1 more course',
                ],
                4 => [
                    'label' => 'Course Explorer',
                    'description' => 'Enroll in 1 more course and master the art of learning',
                ],
            ],
        ],
        'knowledge_paragon' => [
            'name' => 'Knowledge Paragon',
            'description' => 'Welcomes new users and celebrates their first step',
            'completion_label' => 'Congratulations! You are a true Knowledge Paragon!',
            'levels' => [
                1 => [
                    'label' => 'Spark',
                    'description' => 'Complete 1 course',
                ],
                2 => [
                    'label' => 'Flame',
                    'description' => 'Complete 2 courses',
                ],
                3 => [
                    'label' => 'Blaze',
                    'description' => 'Complete 3 courses',
                ],
                4 => [
                    'label' => 'Inferno',
                    'description' => 'Complete 5 courses within first month of joining',
                ],
            ],
        ],
        'skill_vanguard' => [
            'name' => 'Skill Vanguard',
            'description' => 'Rewards dedication to broad skill development',
            'completion_label' => 'Congratulations! You are a true Skill Vanguard!',
            'levels' => [
                1 => [
                    'label' => 'Spark',
                    'description' => 'Complete 5 courses in any category',
                ],
                2 => [
                    'label' => 'Flame',
                    'description' => 'Complete 10 courses in any category',
                ],
                3 => [
                    'label' => 'Blaze',
                    'description' => 'Complete 20 courses in any category',
                ],
                4 => [
                    'label' => 'Inferno',
                    'description' => 'Complete 30 courses in any category',
                ],
            ],
        ],
        'mastery_sage' => [
            'name' => 'Mastery Sage',
            'description' => 'Recognizes academic excellence',
            'completion_label' => 'Congratulations! You are a true Mastery Sage!',
            'levels' => [
                1 => [
                    'label' => 'Spark',
                    'description' => 'Perfect score on 1 course assessment',
                ],
                2 => [
                    'label' => 'Flame',
                    'description' => 'Perfect score on 3 course assessments',
                ],
                3 => [
                    'label' => 'Blaze',
                    'description' => 'Perfect score on 5 course assessments',
                ],
                4 => [
                    'label' => 'Inferno',
                    'description' => 'Perfect score on 10 course assessments',
                ],
            ],
        ],
        'learning_epic' => [
            'name' => 'Learning Epic',
            'description' => 'Celebrates extensive learning achievements',
            'completion_label' => 'Congratulations! You have achieved Learning Epic status!',
            'levels' => [
                1 => [
                    'label' => 'Spark',
                    'description' => 'Complete 10 courses across categories',
                ],
                2 => [
                    'label' => 'Flame',
                    'description' => 'Complete 20 courses across categories',
                ],
                3 => [
                    'label' => 'Blaze',
                    'description' => 'Complete 30 courses across categories',
                ],
                4 => [
                    'label' => 'Inferno',
                    'description' => 'Complete 50 courses across categories',
                ],
            ],
        ],
        'feedback_maestro' => [
            'name' => 'Feedback Maestro',
            'description' => 'Encourages active participation in platform improvement',
            'completion_label' => 'Congratulations! You are a true Feedback Maestro!',
            'levels' => [
                1 => [
                    'label' => 'Spark',
                    'description' => 'Provide feedback on 5 courses/resources',
                ],
                2 => [
                    'label' => 'Flame',
                    'description' => 'Provide feedback on 10 courses/resources',
                ],
                3 => [
                    'label' => 'Blaze',
                    'description' => 'Provide feedback on 20 courses/resources',
                ],
                4 => [
                    'label' => 'Inferno',
                    'description' => 'Provide feedback on 50 courses/resources',
                ],
            ],
        ],
        'steadfast_monarch' => [
            'name' => 'Steadfast Monarch',
            'description' => 'Builds long-term engagement',
            'completion_label' => 'Congratulations! You are a true Steadfast Monarch!',
            'levels' => [
                1 => [
                    'label' => 'Spark',
                    'description' => 'Engage with platform for 25 consecutive days',
                ],
                2 => [
                    'label' => 'Flame',
                    'description' => 'Engage with platform for 30 consecutive days',
                ],
                3 => [
                    'label' => 'Blaze',
                    'description' => 'Engage with platform for 60 consecutive days',
                ],
                4 => [
                    'label' => 'Inferno',
                    'description' => 'Engage with platform for 90 consecutive days',
                ],
            ],
        ],
        'certification_vanguard' => [
            'name' => 'Certification Vanguard',
            'description' => 'Motivates verifiable outcomes',
            'completion_label' => 'Congratulations! You are a true Certification Vanguard!',
            'levels' => [
                1 => [
                    'label' => 'Spark',
                    'description' => 'Earn 2 course certificates',
                ],
                2 => [
                    'label' => 'Flame',
                    'description' => 'Earn 5 course certificates',
                ],
                3 => [
                    'label' => 'Blaze',
                    'description' => 'Earn 10 course certificates',
                ],
                4 => [
                    'label' => 'Inferno',
                    'description' => 'Earn 20 course certificates',
                ],
            ],
        ],
    ],
    'points' => [
        'mission_level' => ':mission - Level :level',
        'mission_completion' => ':mission - Completion',
    ],
    'badges' => [
        'mission_level' => ':mission - Level :level',
        'mission_master' => ':mission - Master',
        'level_description' => 'Complete level :level of :mission',
        'completion_description' => 'Complete all levels of :mission',
    ],
    'common' => [
        'mission_complete' => 'Mission Complete',
        'unknown' => 'Unknown',
        'congratulations' => 'Congratulations! Mission accomplished!',
    ],
];
