<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\GstRate;
use App\Models\PrintSetting;
use App\Models\Supplier;
use App\Models\Company;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['code'=>'CUST001','name'=>'ABC Steel Pvt. Ltd.','contact_person'=>'Rajesh Sharma','phone'=>'9876543210','email'=>'abcsteel@example.com','gst_no'=>'27ABCDE1234F1Z5','address'=>'Nagpur, Maharashtra','opening_balance'=>0,'is_government_employee'=>false,'bill_note'=>null,'is_active'=>true],
            ['code'=>'CUST002','name'=>'Shree Metal Works','contact_person'=>'Amit Patil','phone'=>'9822012345','email'=>'shreemetal@example.com','gst_no'=>'27ABCDE5678K1Z2','address'=>'Bhandara, Maharashtra','opening_balance'=>0,'is_government_employee'=>false,'bill_note'=>null,'is_active'=>true],
            ['code'=>'CUST003','name'=>'Kothari Industries','contact_person'=>'Mahesh Kothari','phone'=>'9765432109','email'=>'kothari@example.com','gst_no'=>'27ABCDE2468L1Z7','address'=>'Mumbai, Maharashtra','opening_balance'=>0,'is_government_employee'=>false,'bill_note'=>null,'is_active'=>true],
            ['code'=>'CUST004','name'=>'R T Jain & Co.','contact_person'=>'Rakesh Jain','phone'=>'9898989898','email'=>'rtjain@example.com','gst_no'=>'27ABCDE1357M1Z3','address'=>'Tarapur, Maharashtra','opening_balance'=>0,'is_government_employee'=>false,'bill_note'=>null,'is_active'=>true],
            ['code'=>'CUST005','name'=>'GEMC Traders','contact_person'=>'Suresh Verma','phone'=>'9123456780','email'=>'gemc@example.com','gst_no'=>'27ABCDE9753N1Z6','address'=>'Jabalpur, Madhya Pradesh','opening_balance'=>0,'is_government_employee'=>false,'bill_note'=>null,'is_active'=>true],
            ['code'=>'CUST006','name'=>'Om Sai Engineering','contact_person'=>'Prakash More','phone'=>'9011223344','email'=>'omsai@example.com','gst_no'=>'27ABCDE8642P1Z4','address'=>'Pune, Maharashtra','opening_balance'=>0,'is_government_employee'=>false,'bill_note'=>null,'is_active'=>true],
            ['code'=>null,'name'=>'Vehicle Factory Jabalpur','contact_person'=>null,'phone'=>'9325393615','email'=>'chauhandivyaraj@gmail.com','gst_no'=>null,'address'=>'PLOT NO 610, ADITYA BUNGLOW KHER SECTION NEAR HERAMB MANDIR, AMBERNATH EAST PIN 421501','opening_balance'=>0,'is_government_employee'=>false,'bill_note'=>null,'is_active'=>true],
            ['code'=>null,'name'=>'THE GENERAL MANAGER MTPF','contact_person'=>'MR MANIL BHOIR','phone'=>'02512612600','email'=>null,'gst_no'=>'27AAVCA6456C1ZD','address'=>'KALYAN BADLAPUR ROAD, AMBARNATH WEST 421502','opening_balance'=>0,'is_government_employee'=>true,'bill_note'=>'GST @5% WILL BE PAID BY SERVICE USER UNDER RCM (If Applicable)','is_active'=>true],
            ['code'=>null,'name'=>'BHUSHAN DESHMUKH','contact_person'=>'BHUSHAN','phone'=>'99992225','email'=>null,'gst_no'=>'NA','address'=>'PLOT NO 555 UMA NIWAS, KHER SECTION AMBARNATH EAST','opening_balance'=>0,'is_government_employee'=>false,'bill_note'=>null,'is_active'=>true],
        ];
        foreach ($customers as $row) {
            Customer::query()->updateOrCreate(['name'=>$row['name']], $row);
        }

        $suppliers = [
            ['code'=>'SUP001','name'=>'Balbir Transport','contact_person'=>'Balbir Singh','phone'=>'9988776655','email'=>'balbir@example.com','gst_no'=>'27ABCDE1111A1Z1','address'=>'Nagpur, Maharashtra','bank_name'=>'HDFC Bank','bank_account'=>'501000100001','ifsc'=>'HDFC0000123','opening_balance'=>0,'is_active'=>true],
            ['code'=>'SUP002','name'=>'Andhra Highway','contact_person'=>'Ramesh Rao','phone'=>'9877001122','email'=>'andhra@example.com','gst_no'=>'27ABCDE2222B1Z2','address'=>'Hyderabad, Telangana','bank_name'=>'ICICI Bank','bank_account'=>'501000100002','ifsc'=>'ICIC0000234','opening_balance'=>0,'is_active'=>true],
            ['code'=>'SUP003','name'=>'Shivnath Logistics','contact_person'=>'Sanjay Patil','phone'=>'9766002233','email'=>'shivnath@example.com','gst_no'=>'27ABCDE3333C1Z3','address'=>'Mumbai, Maharashtra','bank_name'=>'State Bank of India','bank_account'=>'501000100003','ifsc'=>'SBIN0000345','opening_balance'=>0,'is_active'=>true],
            ['code'=>'SUP004','name'=>'Vaibhav Roadlines','contact_person'=>'Vaibhav More','phone'=>'9655003344','email'=>'vaibhav@example.com','gst_no'=>'27ABCDE4444D1Z4','address'=>'Pune, Maharashtra','bank_name'=>'Bank of Maharashtra','bank_account'=>'501000100004','ifsc'=>'MAHB0000456','opening_balance'=>0,'is_active'=>true],
            ['code'=>'SUP005','name'=>'Moryanpur Transport','contact_person'=>'Dinesh Yadav','phone'=>'9544004455','email'=>'moryanpur@example.com','gst_no'=>'27ABCDE5555E1Z5','address'=>'Jabalpur, Madhya Pradesh','bank_name'=>'Axis Bank','bank_account'=>'501000100005','ifsc'=>'UTIB0000567','opening_balance'=>0,'is_active'=>true],
            ['code'=>'SUP006','name'=>'Mahesh Carriers','contact_person'=>'Mahesh Pawar','phone'=>'9433005566','email'=>'mahesh@example.com','gst_no'=>'27ABCDE6666F1Z6','address'=>'Indore, Madhya Pradesh','bank_name'=>'HDFC Bank','bank_account'=>'501000100006','ifsc'=>'HDFC0000678','opening_balance'=>0,'is_active'=>true],
            ['code'=>null,'name'=>'Bombay South Roadways','contact_person'=>null,'phone'=>'9325393615','email'=>'chauhandivyaraj@gmail.com','gst_no'=>null,'address'=>'PLOT NO 610, ADITYA BUNGLOW KHER SECTION NEAR HERAMB MANDIR, AMBERNATH EAST PIN 421501','bank_name'=>null,'bank_account'=>null,'ifsc'=>null,'opening_balance'=>0,'is_active'=>true],
        ];
        foreach ($suppliers as $row) {
            Supplier::query()->updateOrCreate(['name'=>$row['name']], $row);
        }

        $vehicleTypes = [
            ['Tata Ace','Small goods vehicle'],
            ['Pickup 1.5 Ton','1.5 ton pickup vehicle'],
            ['10 FT Truck','10 feet goods truck'],
            ['14 FT Truck','14 feet goods truck'],
            ['17 FT Truck','17 feet goods truck'],
            ['19 FT Truck','19 feet goods truck'],
            ['20 FT Container','20 feet container vehicle'],
            ['22 FT Truck','22 feet goods truck'],
            ['24 FT Truck','24 feet goods truck'],
            ['28 FT Truck','28 feet goods truck'],
            ['32 FT Single Axle','32 feet single axle truck'],
            ['32 FT Multi Axle','32 feet multi axle truck'],
            ['40 FT Trailer','40 feet trailer'],
            ['28 MT Trailer','Approx. 28 metric ton trailer'],
            ['35 MT Trailer','Approx. 35 metric ton trailer'],
        ];
        foreach ($vehicleTypes as [$name,$description]) {
            VehicleType::query()->updateOrCreate(['name'=>$name], ['description'=>$description,'is_active'=>true]);
        }

        foreach (['BGT','LST'] as $name) {
            Company::query()->firstOrCreate(
                ['name' => $name],
                ['gst_no' => '', 'address' => '']
            );
        }

        foreach ([0,5,18,28] as $rate) {
            GstRate::query()->updateOrCreate(
                ['rate'=>$rate],
                ['name'=>$rate.'%','is_active'=>true,'is_default'=>$rate===0]
            );
        }

        PrintSetting::query()->firstOrCreate([], ['letterhead_top_margin_mm'=>0]);
    }
}
