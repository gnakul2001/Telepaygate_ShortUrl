<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortUrls extends Model
{
    protected $table='ShortUrls';
    protected $fillable = ['short_code','long_url','ip_data','counter','type','isTemp','isUsed'];
}
?>
