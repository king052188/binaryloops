<?php

namespace king052188\BinaryLoops;

use Illuminate\Support\Facades\Config;

use DB;


class BLHelper
{
    public function get_member_info($uid)
    {
        $d = DB::table('user_account')
             ->where('Id', '=', (int)$uid)
             ->first();

        return $d;
    }

    public function generate_number($country = null)
    {
      $prefix = "";
      if($country != "") {
        switch ($country) {
          case 'US':
            $prefix = 1 + (int)date("y");
            break;
          default:
            $prefix = 63 + (int)date("y");
            break;
        }
      }
      $t = explode( " ", microtime() );
      $mil = substr($t[1], 5, 10) . substr($t[0], 3, 6);
      $mil_2 = $t[1];
      $c = date("md");
      $uuid = $prefix . $c . $mil;
      return substr($uuid, 0, 4) . '-' . substr($uuid, 4, 4) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4);
    }

    public function generate_unique_id($country = null)
    {
        // check the country code
        $country_code = $country == null ? "PH" : $country;
        $member_uid = null;
        // generate member unique id
        do {
            $member_uid = $this->generate_number($country_code);
            $u = DB::select("SELECT member_uid FROM user_account WHERE member_uid = '{$member_uid}';");
        } while ($u != null);

        if($u == null) {
            return $member_uid;
        }
    }

    public function check_username($username, $is_sponsor)
    {
       $u = User::where("username", "=", $username)->first();
       if($is_sponsor) {
           return $u->member_uid;
       }
       else {
           if($u == null) {
               return $username;
           }
           return null;
       }
    }

    public function check_activation_code($code, $isDone = false)
    {
        if($isDone) {
            $c = DB::table('user_activation_code')
                  ->where('code_', $code)
                  ->update(['code_status' => 2]);
            return $c;
        }

        $c = DB::table('user_activation_code')
              ->where('code_', $code)
              ->where('code_status', 1)
              ->first();

        if( $c != null) {
            return array('Activation' => $c);;
        }
        return null;
    }

    public function check_is_crossline($sponsor_uid, $placement_uid)
    {
        $placement_uid = $placement_uid;
        $lookup_ = [];
        $ctr = 0;

        if($sponsor_uid == $placement_uid) {
            return false;
        }

        do {
            $genealogy = DB::table('user_genealogy_transaction')
                         ->where('member_uid', $placement_uid)
                         ->first();

            if($genealogy != null) {
                if($ctr == 0)
                {
                    unset($lookup_);
                }
                $lookup_[] = $genealogy;
                $placement_uid = $genealogy->placement_id;
                if($sponsor_uid == $placement_uid) {
                    return false;
                }
                $ctr++;
            }
        } while ( $genealogy != null );

        return true;
    }

    public function check_position_of_placement($member_id, $position_id)
    {
        $p = DB::select("
            SELECT Id FROM user_genealogy_transaction
            WHERE placement_id = '". $member_id ."'
            AND position_ = ". $position_id ." AND position_ > 1 AND status_ != -99;
        ");
        if($p != null) {
            return $p[0]->Id;
        }
        else {
            return 0;
        }
    }

    public function add_member($member_info)
    {
      // $member_info = array(
      //   "member_uid" => 0,
      //   "username" => 0,
      //   "password" => 0,
      //   "first_name" => 0,
      //   "last_name" => 0,
      //   "country_" => 0,
      //   "email_" => 0,
      //   "mobile_" => 0,
      //   "type_" => 0,
      //   "status_" => -1,
      //   "connected_to" => 0,
      //   "activation_id" => 0,
      // );
      
      $id = DB::table('user_account')->insertGetId(
                $member_info
            );

      return ["Insert_GetId" => $id];
    }

    public function add_member_genealogy($data)
    {

      $data = array(
        "transaction" => 0,
        "sponsor_id" => 0,
        "placement_id" => 0,
        "member_uid" => 0,
        "activation_code" => 0,
        "position_" => 0,
        "status_" => 0,
      );

      $id = DB::table('user_genealogy_transaction')->insertGetId(
                $data
            );
      return $id;
    }

    public function lookup_genealogy($member_uid)
    {
       $users = DB::select("
           SELECT t.sponsor_id, t.placement_id, t.member_uid, t.position_,
           a.username, a.mobile_, a.type_, a.status_
           FROM user_genealogy_transaction AS t
           INNER JOIN user_account AS a
           ON t.member_uid = a.member_uid
           WHERE a.member_uid = '{$member_uid}' AND a.status_ != -99;
       ");

       if($users == null) {
           return false;
       }

       if($users[0]->type_ > 0) {
           $lookup_ = $this->lookup_process(
               $users[0]->member_uid,
               $users[0]->position_,
               $users[0]->type_
           );
       }

       return array("status" => true);
    }

    public function lookup_process($member_uid, $position, $points)
    {
        $status[] = array("Code" => -99);
        $m_uid = $member_uid;
        $ctr = 0;
        do{
            $users = DB::select("
            SELECT t.sponsor_id, t.placement_id, t.member_uid, t.position_,
            a.username, a.mobile_, a.type_, a.status_
            FROM user_genealogy_transaction AS t
            INNER JOIN user_account AS a
            ON t.member_uid = a.member_uid
            WHERE t.member_uid = '{$m_uid}';
            ");

            if($users != null) {
                $data = [];
                if($ctr == 0)
                {
                    unset($status);
                    $data = array(
                      "member_uid" => $users[0]->placement_id,
                      "position_id" => $position,
                      "type_id" => $users[0]->type_,
                      "points" => $points,
                    );
                }
                else
                {
                    $data = array(
                      "member_uid" => $users[0]->placement_id,
                      "position_id" => $users[0]->position_,
                      "type_id" => $users[0]->type_,
                      "points" => $points,
                    );
                }

                $sum = DB::table('user_account')->insertGetId($data);
                $status[] = array("Code" => $sum);
                $m_uid = $users[0]->placement_id;
                $ctr++;
            }
        }while ( $users != null );
        return $status;
    }
}
