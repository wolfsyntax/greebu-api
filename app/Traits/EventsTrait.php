<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Profile;
use App\Models\Event;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use App\Libraries\AwsService;
use Intervention\Image\Facades\Image;

trait EventsTrait
{

    public function fetchEvents($request, $offset, $perPage = 12, $type = 'ongoing') {

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');
        $city = $request->query('city', '');
        $cost = $request->query('cost', '');
        $event_type = $request->query('event_type', '');

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        $events = Event::query();

        $events->when($event_type, function ($query, $event_type) {
            return $query->where('event_type', 'LIKE', '%' . $event_type . '%');
        });

        $events->when($city, function ($query, $city) {
            return $query->where('city', 'LIKE', '%' . $city . '%');
        });

        $events->when(in_array($cost, ['paid', 'free']), function ($query, $cost) {
            return $query->where(
                'is_free',
                strtolower($cost) === 'free' ? true : false
            );
        });

        $events->when($search !== '', function ($query, $search) {
            return $query->where('event_name', 'LIKE', '%' . $search . '%')
                ->orWhere('venue_name', 'LIKE', '%' . $search . '%')
                ->orWhereHas('profile', function ($query) use ($search) {
                return $query->where('business_name', 'LIKE', '%' . $search . '%');
            });
        });

        $events->where('start_date', '>=', now()->addDays(1)->isoFormat('YYYY-MM-DD'));

        if ($type === 'ongoing') {
            $events->whereBetween('start_date', [$now, $endOfWeek])
            ->orderBy('start_date', $orderBy)->orderBy('start_time', 'ASC');
        } else if ($type === 'upcoming') {
            $events->where('start_date', '>', $endOfWeek)->orderBy('start_date', $orderBy)->orderBy('start_time', 'ASC');
        } else if ($type === 'past') {
            $events->where('end_date', '<', $now)->orderBy('start_date', $orderBy)->orderBy('created_at', $orderBy);
        } else {
            $events->orderBy('created_at', $orderBy);
        }

        return $events->skip($offset)->take($perPage)->get();
    }
}
