<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Ticket;
use App\Models\BusCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AdminBusesController extends Controller
{
    // middleware
    public function __construct()
    {
        $this->middleware('auth')->only(['index', 'create', 'store', 'edit', 'update', 'destroy', 'show']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        /* all() and get() output the same thing in this case but
        get() allow you to use chaining methods while all() do not */
        $buses = DB::table('buses')->select('buses.*', 'bus_companies.Ten_NX')->join('bus_companies', 'buses.IdNX', '=', 'bus_companies.IdNX')->paginate(15);
        return view('buses.index', [
            'buses' => $buses,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('buses.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->merge([
            'So_xe' => strtoupper($request->So_xe),
            'IdNX' => strtoupper($request->IdNX)
        ]);

        $request->validate([
            'So_xe' => 'required|regex:/^[0-9]{2}[A-Z]{1}-[0-9]{4,5}$/|unique:buses',
            'Doi_xe' => 'required|numeric|between:1990,2022',
            'So_Cho_Ngoi' => 'required|numeric|between:29,45',
            'IdNX' => 'required|exists:bus_companies,IdNX',
        ]);


        $count = DB::table('buses')->count() + 1;
        while (true) {
            $IdXe = $request->IdNX . '-' . $count;
            if (!DB::table('buses')->where('IdXe', $IdXe)->exists()) {
                break;
            }
            $count++;
        }

        // Insert bằng eloquent
        Bus::create([
            'IdXe' => 'B' . $count,
            'So_xe' => $request->So_xe,
            'Doi_xe' => $request->Doi_xe,
            'So_Cho_Ngoi' => $request->So_Cho_Ngoi,
            'Loai_xe' => $request->Loai_xe,
            'IdNX' => $request->IdNX
        ]);

        return redirect()->route('buses.index')
            ->with('message', 'Thêm xe thành công!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($IdXe)
    {
        $bus = DB::table('buses')->select('buses.*', 'bus_companies.Ten_NX')
            ->join('bus_companies', 'buses.IdNX', '=', 'bus_companies.IdNX')
            ->where('IdXe', $IdXe)
            ->first();

        return view('buses.show', [
            'bus' => $bus
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($IdXe)
    {
        return view('buses.edit', [
            'bus' => Bus::where('IdXe', $IdXe)->firstOrFail()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $IdXe)
    {

        $request->validate([
            'So_xe' => 'required|regex:/^[0-9]{2}[A-Z]{1}-[0-9]{4,5}$/|unique:buses,So_xe,' . $IdXe . ',IdXe',
            'Doi_xe' => 'required|numeric|between:1990,2022',
            'So_Cho_Ngoi' => 'required|numeric|between:29,45',
            'IdNX' => 'required|exists:bus_companies,IdNX',
        ]);

        // update dữ liệu, cách ngắn gọn hơn, except method dùng để bỏ qua _token và _method
        // Bắt buộc input phải có name giống với tên cột trong DB

        Bus::where('IdXe', $IdXe)->update($request->except('_token', '_method'));

        return redirect()->route('buses.show', $IdXe)
            ->with('message', 'Sửa thông tin xe thành công!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($IdXe)
    {
        $trips = DB::table('trips')->where('IdXe', $IdXe)->get()->toArray();

        $tickets = array();
        foreach ($trips as $trip) {
            $tickets = array_merge($tickets, Ticket::where('IdChuyen', $trip->IdChuyen)->get()->toArray());
        }

        foreach ($tickets as $ticket)
            DB::table('ticket_details')->where('IdBanVe', $ticket['IdBanVe'])->delete();

        foreach ($trips as $trip)
            DB::table('tickets')->where('IdChuyen', $trip->IdChuyen)->delete();



        // Xóa xe
        Bus::where('IdXe', $IdXe)->delete();

        return redirect()->route('buses.index')
            ->with('message', 'Xóa xe thành công!');
    }
}