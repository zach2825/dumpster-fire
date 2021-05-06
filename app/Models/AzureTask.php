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
 * @property array createdby
 * @property Carbon createddate
 * @property array changedby
 * @property Carbon changeddate
 * @property string commentcount
 * @property string title
 * @property string boardcolumn
 * @property string boardcolumndone
 * @property string boardlane
 * @property string description
 * @property string creatorName
 */
class AzureTask extends Model
{
    protected $guarded = [];

    protected $dates = ['createddate', 'changeddate'];

    protected $appends = ['creatorName', 'assignedToName', 'workType'];

    public function getCreatorNameAttribute()
    {
        return $this->createdby['displayName'] ?? '';
    }

    public function getAssignedToNameAttribute()
    {
        return $this->assignedto['displayName'] ?? '';
    }

    public function getWorkTypeAttribute()
    {
        return $this->workitemtype;
    }
}
