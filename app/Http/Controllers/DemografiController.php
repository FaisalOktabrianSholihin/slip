<?php
namespace App\Http\Controllers;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
class DemografiController extends Controller {
 public function show() { $s=Setting::current(); return response()->json(['ageRanges'=>$s->demography_age_ranges ?: [['label'=>'25–35','min'=>25,'max'=>35],['label'=>'36–45','min'=>36,'max'=>45],['label'=>'>45','min'=>46,'max'=>null]],'educationLevels'=>$s->demography_education_levels ?: ['S1','DIII','SMA','SMP']]); }
 public function update(Request $r) {
  $d=$r->validate(['ageRanges'=>'required|array|max:5','ageRanges.*.label'=>'required|string|max:40','ageRanges.*.min'=>'required|integer|min:0|max:100','ageRanges.*.max'=>'nullable|integer|min:0|max:120','educationLevels'=>'required|array|max:5','educationLevels.*'=>'required|string|max:30']);
  foreach($d['ageRanges'] as $range) if(isset($range['max']) && $range['max'] < $range['min']) return response()->json(['message'=>'Batas maksimal usia harus lebih besar atau sama dengan batas minimal.'],422);
  $s=Setting::current(); $s->update(['demography_age_ranges'=>array_values($d['ageRanges']),'demography_education_levels'=>array_values($d['educationLevels'])]);
  ActivityLog::catat('Ubah demografi','Mengubah kelompok usia dan tingkat pendidikan pada dashboard.'); return $this->show();
 }
}
