<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PhoneBookController extends Controller
{

    public function index(Request $request)
    {
        $validator = $request->validate([
            'object' => "required|in:department,sites",
            'domain' => "required",
            'mac' => "required"
        ]);

        $access_token = '75d6c0b42c1d9a212903928872e7829f';

        $mac = Http::withToken($access_token)->post(config('connectware.url').'/ns-api/?object=mac&action=read&format=json&mac='.$request->mac.'');

        if(array_key_exists(strtolower('auth_user'), $mac->json()[0]) && array_key_exists(strtolower('auth_pass'), $mac->json()[0])){
            // if($mac->json()[0]['auth_user'] != null && $mac->json()[0]['auth_pass'] != null){
                return $this->handle($request,$access_token);
            // }
        }else{
            return response()->noContent(404);
        }
    }

    public function handle($request,$access_token)
    {
        $contacts = Http::withToken($access_token)->post(config('connectware.url').'/ns-api/?object=contact&action=read&domain='.$request->domain.'&format=json&includeDomain=yes&user=1003');

        $groupData = Http::withToken($access_token)->post(config('connectware.url').'/ns-api/?action=list&format=json&object='.$request->object.'&domain='.$request->domain.'');

        $group = $groupData->json();

        //Add others
        if ((array_search(strtolower('Others'), $group)) == false) {
            array_push($group, 'Others');
        }
        // Add shared
        if ((array_search(strtolower('Shared'), $group)) == false) {
            array_push($group, 'Shared');
        }

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><AddressBook></AddressBook>');
        $xml = $this->groupMerge($xml,$group);
        $xml = $this->contactMerge($xml,$contacts->json(),$group,$request->object);

        return $xml->asXML();

        // return response($xml->asXML(), 200)
        //           ->header('Content-Type', 'text/plain');
    }

    public function groupMerge($xml,$data)
    {

        if (($key = array_search('n/a', $data)) !== false) {
            unset($data[$key]);
        }

        foreach ($data as $key => $value) {
            $datas = $xml->addChild('pbgroup');
            $datas->addChild('id', htmlspecialchars($key));
            $datas->addChild('name', htmlspecialchars($value));
        }

        return $xml;
    }

    public function contactMerge($xml,$contacts,$group,$type)
    {

        foreach ($contacts as $key => $value) {
            $datas = $xml->addChild('Contact');
            $datas->addChild('id', htmlspecialchars($key));
            $datas->addChild('FirstName', htmlspecialchars($value['first_name']));
            $datas->addChild('LastName', htmlspecialchars($value['last_name']));
            $datas->addChild('Frequent', htmlspecialchars(0));

            $phone = $datas->addChild('Phone');
            $phone->addAttribute('type', 'Work');
            $phone->addChild('phonenumber', htmlspecialchars(100));
            $phone->addChild('accountindex', htmlspecialchars(0));

            $datas->addChild('Group', htmlspecialchars($this->identifyGroup($value, $group, $type)));
            $datas->addChild('Primary', htmlspecialchars(0));
        }

        return $xml;
    }

    public function identifyGroup($contact, $groups, $type)
    {

        if ((array_search('Shared', $contact)) !== false) {
            return array_search('Shared', $groups);
        }

        $category = ($type == 'department') ? 'group' : 'site';
        if (array_key_exists($category, $contact) && $contact[$category] !== '') {
            return array_search($contact[$category], $groups);
        }

        return array_search('Others', $groups);
    }
}
