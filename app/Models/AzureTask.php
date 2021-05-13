<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AzureTask
 * @package App\Models
 * @property string areapath
 * @property string teamproject
 * @property string iterationpath
 * @property string workitemtype
 * @property string state
 * @property string reason
 * @property array  createdby
 * @property Carbon createddate
 * @property array  changedby
 * @property Carbon changeddate
 * @property string commentcount
 * @property string title
 * @property string boardcolumn
 * @property string boardcolumndone
 * @property string boardlane
 * @property string description
 * @property string creatorName
 * @property string assignedToName
 * @property string url
 * @property string transitionColumnName
 * @property string boardColumn
 * @property string task_id
 */
class AzureTask extends Model
{

    protected $fillable = [
        'url',
        'assignedTo',
        'creatorName',
        'itemType',
        'itemStatus',
        'task_id',
        'title',
        'transitionColumnName',
        'originalJson',
    ];

    protected $dates = ['createdDate', 'changedDate'];

    protected $appends = ['creatorName', 'assignedToName', 'workType'];

    protected $casts = [
        'originalJson' => 'json',
    ];

    public function getCreatorNameAttribute()
    {
        return $this->createdby['displayName'] ?? '';
    }

    public function getAssignedToNameAttribute()
    {
        return $this->assignedto['displayName'] ?? '';
    }

    public function getBoardColumnAttribute()
    {
        return $this->originalJson['fields']['System.BoardColumn'] ?? 'n/a';
    }

    public function getWorkTypeAttribute()
    {
        return $this->workitemtype;
    }

//    public function getUrlAttribute()
//    {
//        return sprintf('https://dev.azure.com/%s/%s/_workitems/edit/%s', config('df.organization'), config('df.project'), $this->id);
//    }
}
