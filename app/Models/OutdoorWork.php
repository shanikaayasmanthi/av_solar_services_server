<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutdoorWork extends Model
{
    protected $table = 'outdoor_works';

     /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

     protected $fillable = [
        'service_id',
        // 'project_id',
        'CEB_import_reading',
        'CEB_import_reading_comments',
        'CEB_export_reading',
        'CEB_export_reading_comments',
        'round_resistence',
        'round_resistence_comments',
        'earthing_rod_connection',
        'earthing_rod_connection_comments',
     ];

     public function service()
     {
        return $this->belongsTo(Service::class);
     }

    //  public function Project()
    //  {
    //     return $this->belongsTo(Project::class);
    //  }
}
