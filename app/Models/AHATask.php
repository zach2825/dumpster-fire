<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AHATask
 * @package App\Models
 * @property string id
 * @property string name
 * @property string reference_num
 * @property string position
 * @property string score
 * @property string created_at
 * @property string updated_at
 * @property string start_date
 * @property string due_date
 * @property string product_id
 * @property string progress
 * @property string progress_source
 * @property array workflow_kind
 * @property string workflow_status
 * @property string description
 * @property string attachments
 * @property string integration_fields
 * @property string url
 * @property string resource
 * @property string release
 * @property string master_feature
 * @property string epic
 * @property string created_by_user
 * @property string assigned_to_user
 * @property string requirements
 * @property string goals
 * @property string comments_count
 * @property string score_facts
 * @property string tags
 * @property string full_tags
 * @property string custom_fields
 * @property string feature_links
 * @property string feature_only_original_estimate
 * @property string feature_only_remaining_estimate
 * @property string feature_only_work_done
 * @property string workType
 */
class AHATask extends Model
{
    protected $guarded = [];

    protected $appends = ['title', 'workType'];

    public function getTitleAttribute()
    {
        return $this->name;
    }

    public function getWorkTypeAttribute()
    {
        return $this->workflow_kind['name'] ?? 'feature';
    }
}
