<?php
namespace Voilaah\Gamify\Components;

use Auth;
use Cms\Classes\ComponentBase;
use Voilaah\Gamify\Models\Reputation;

/**
 * UserReputation Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class UserReputation extends ComponentBase
{
    public $items;
    public $perPage;
    public $page;


    public function componentDetails()
    {
        return [
            'name' => 'Points Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [
            'pageNumber' => [
                'title' => 'Page number',
                'description' => 'This value is used to determine what page the user is on.',
                'type' => 'string',
                'default' => '{{ :page }}',
            ],
            'perPage' => [
                'title' => 'Number of reputation to display',
                'type' => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Invalid format of the courses per page value',
                'default' => '10',
            ],
        ];
    }

    public function onRun()
    {
        if ($user = Auth::getUser()) {
            $this->prepareVars();
            $this->items = $this->loadUserReputation($user);
        }
    }

    public function prepareVars()
    {
        $this->page = $this->property('pageNumber');
        $this->perPage = $this->property('perPage') ?: 1;
    }

    public function loadUserReputation($user)
    {
        $query = Reputation::query()
            ->payeeId($user->id)
            ->with(['payee', 'subject'])
            ->orderBy('updated_at', 'desc');
        return $query->paginate($this->perPage, $this->page);
    }
}
