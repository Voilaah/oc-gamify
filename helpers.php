<?php

use RainLab\User\Models\User;

if (!function_exists('getUserRank')) {

    /**
     * Get the user rank based on his point and the rest of the user
     *
     * @param null $user
     */
    function getUserRank($user)
    {
        $selectUserRank = DB::raw("COUNT(1) as ranking");
        $userRank = User::select($selectUserRank)
            ->orderByDesc($user->getReputationField())
            ->orderBy('last_name', 'ASC')
            ->where($user->getReputationField(), '>=', $user->getPoints())
            ->first();
        return $userRank['ranking'];
    }
}

if (!function_exists('givePoint')) {

    /**
     * Give point to user
     *
     * @param \Voilaah\Gamify\Classes\PointType $pointType
     * @param null $payee
     */
    function givePoint(\Voilaah\Gamify\Classes\PointType $pointType, $payee = null)
    {
        $payee = $payee ?? config('gamify.auth_base')::user();

        if (!$payee) {
            return;
        }

        $payee->givePoint($pointType);

        // check if we have previous points earned
        $existingPointsNotifications = Session::get('gamify.points.earned');
        if (!is_array($existingPointsNotifications)) {
            $existingPointsNotifications = [];
        }

        $existingPointsNotifications[] = [
            'name' => $pointType->name,
            // 'description' => $pointType->description,
            'type' => get_class($pointType),
            'points' => $pointType->points,
            // 'icon' => $pointType->icon,
        ];

        Session::put('gamify.points.earned', $existingPointsNotifications);
    }
}

if (!function_exists('undoPoint')) {

    /**
     * Undo a given point
     *
     * @param \Voilaah\Gamify\Classes\PointType $pointType
     * @param null $payee
     */
    function undoPoint(\Voilaah\Gamify\Classes\PointType $pointType, $payee = null)
    {
        $payee = $payee ?? config('gamify.auth_base')::user();

        if (!$payee) {
            return;
        }

        $payee->undoPoint($pointType);
    }
}

if (!function_exists('short_number')) {

    /**
     * Convert large positive numbers in to short form like 1K+, 100K+, 199K+, 1M+, 10M+, 1B+ etc
     *
     * @param $n int
     * @return string
     */
    function short_number($n)
    {
        if ($n >= 0 && $n < 1000) {
            $n_format = floor($n);
            $suffix = '';
        } else if ($n >= 1000 && $n < 1000000) {
            $n_format = floor($n / 1000);
            $suffix = 'K+';
        } else if ($n >= 1000000 && $n < 1000000000) {
            $n_format = floor($n / 1000000);
            $suffix = 'M+';
        } else {
            $n_format = floor($n / 1000000000);
            $suffix = 'B+';
        }

        return !empty($n_format . $suffix) ? $n_format . $suffix : '0';
    }
}

if (!function_exists('simulateCourseEnrollment')) {
    /**
     * Demo helper function to simulate course enrollment for testing CourseExplorerMission
     * 
     * @param \RainLab\User\Models\User $user
     * @param mixed $course (optional course data)
     */
    function simulateCourseEnrollment($user, $course = null)
    {
        // Fire the course enrollment event that the mission listens for
        Event::fire('skillup.course.enrolled', [$user, $course]);
        
        \Log::info("Simulated course enrollment for user {$user->id}");
    }
}
