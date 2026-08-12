@php
    $monthlyEmployeeCount = max(1, count($users));
    $monthlyRowHeight = max(4.5, min(15, 118 / $monthlyEmployeeCount));
    $monthlyNameFont = $monthlyEmployeeCount > 18 ? 2.1 : ($monthlyEmployeeCount > 12 ? 2.5 : 3);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Attendance Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.4.1/paper.css">
  <style>
    @page { size: A4 landscape }
    body { color: #17251d; }
    .report-brand {
        border-bottom: 1.5px solid #1a5632;
        padding-bottom: 2.5mm;
        text-align: center;
    }
    .report-logo {
        width: 16mm;
        height: 16mm;
        object-fit: contain;
        display: block;
        margin: 0 auto 1.5mm;
    }
    .report-school {
        color: #1a5632;
        font-weight: 800;
        font-size: 6mm;
        line-height: 1.05;
        letter-spacing: .1mm;
    }
    .report-address {
        color: #52645a;
        font-size: 2.8mm;
        font-weight: 600;
    }
    .report-title {
        color: #0b2415;
        font-size: 3.6mm;
        font-weight: 800;
        margin-top: 1mm;
    }
    .report-meta {
        font-size: 2.7mm;
        color: #52645a;
    }
    .table-hajiri td {
        border-color: #26372c !important;
    }
    .signature-box {
        border: 1px solid #1a5632;
        border-radius: 2mm;
        min-height: 12mm;
        padding: 1.5mm;
        text-align: center;
        background: #fbfdfb;
    }
    .signature-line {
        border-top: 1px solid #52645a;
        margin: 3mm 4mm 1mm;
    }
    .signature-title {
        color: #0b2415;
        font-size: 3mm;
        font-weight: 800;
    }
    .signature-label {
        color: #52645a;
        font-size: 2.4mm;
        font-weight: 600;
    }

    .date_90 {
        -webkit-transform: rotate(270deg);
        -moz-transform: rotate(270deg);
        -o-transform: rotate(270deg);
        -ms-transform: rotate(270deg);
        transform: rotate(270deg);
        max-height: {{ max(3.5, $monthlyRowHeight - 1) }}mm;
        overflow: hidden;
    }
  </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
</head>

<body class="A4 landscape">
    <section id="splashPage" class="sheet padding-10mm">
        <article class="w-100">
            <div class="w-100 text-center">
                <img style="width: 26mm; display: block; margin: 0 auto 3mm;" src="{{ $siteSettings->logoUrl() }}" alt="">
                <div class="report-school">{{ $siteSettings->localized('site_name', 'School') }}</div>
                <div class="report-address">{{ $siteSettings->localized('site_address', 'Nepal') }}</div>
                <div class="w-100 text-center report-title">मासिक हाजिरी / Attendance Report · <span class="date-text">{{$nowData['yearBS']}}</span> {{$nowData['nmonthBS']}}</div>

            </div>
                <div class="w-100 text-center mt-5 h3"><span id="numOfLoadedUser">0</span> out of {{count($users)}} Loaded</div>
            </div>
            <div class="w-100 d-none">
                <table class="table-hajiri table table-bordered d-none">
                    @foreach($users as $user)
                        @php $designationLabel = $user->designation->label ?? ''; @endphp
                        <tr class="d-flex hajiri-data-table" style="height:{{ $monthlyRowHeight }}mm;" data-device-id="{{$user->device_id}}" id="tr-user-{{$user->device_id}}">
                            <td style="width:45mm; font-size:{{ $monthlyNameFont }}mm; vertical-align: middle;"><b>{{$user["name"]}} <span style="font-size:2mm;">{{$user['device_id']}}</span></b><br/><span style="font-size:{{ $monthlyEmployeeCount > 12 ? '2mm' : '2.5mm' }}">{{ $designationLabel }}</span></td>
                            <td style="width:230mm; font-size:3mm;">Loading Data for Employee: <b>{{$user->name}}</b> : {{$user->device_id}}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </article>
    </section>
</body>
<script>
var xlsxDSA = [];

@foreach($users as $user)
    xlsxDSA[@json((string) $user['device_id'])] = [
        @json(($user['name'] ?? '') . ':' . ($user['device_id'] ?? '')),
        @json($user['designation']['label'] ?? ''),
        0
    ];
@endforeach

var countUser = {
    aInternal: 0,
    aListener: function(val) {},
    set a(val) {
        this.aInternal = val;
        this.aListener(val);
    },
    get a() {
        return this.aInternal;
    },
    registerListener: function(listener) {
        this.aListener = listener;
    }
};

$(document).ready(function(){

    var pageHeader  = '<div class="w-100 report-brand">'+
                            '<img class="report-logo" src="{{ $siteSettings->logoUrl() }}" alt="{{ $siteSettings->localized('site_name', 'School') }}">'+
                            '<div class="report-school">{{ $siteSettings->localized('site_name', 'School') }}</div>'+
                            '<div class="report-address">{{ $siteSettings->localized('site_address', 'Nepal') }}</div>'+
                            '<div class="w-100 report-title" onclick="download();">मासिक हाजिरी / Attendance Report · <span class="date-text">{{$nowData['yearBS']}}</span> {{$nowData['nmonthBS']}}</div>'+
                            '<div class="report-meta">Generated: {{ now()->format('d M Y, h:i A') }}</div>'+
                            @if($labelDepart != "")
                            '<div class="w-100 report-meta">Department: <strong>{{$labelDepart}}</strong></div>'+
                            @endif
                        '</div>';

    var tableHeader =  '<tr id="tableTopRow"  class="d-flex">'+
                        '<td  style="width:45mm; font-size:3mm;">बार</td>'+
    @for($i=$nowData['firstBS'],$j = $nowData['firstDay'];$i<=$nowData['lastBS'];$i++,$j += ($j == 7)?-6:1)
                        '<td style="width:7mm; font-size:2.6mm;" class="text-center">{{$npCal->get_day_abbr($j)}}</td>'+
    @endfor
                        '<td style="width:12mm;  font-size:2mm;" class="text-center">Remarks</td>'+
                    '</tr>'+
                    '<tr id="tableSecondRow"  class="d-flex">'+
                        '<td style="width:45mm; font-size:3mm;">गते</td>'+
    @php $countADindex = 0 @endphp
    @for($i=$nowData['firstBS'];$i<=$nowData['lastBS'];$i++,$countADindex++)
                        '<td style="width:7mm; font-size:2.6mm;" title="{{$nowData['periodAD'][$countADindex]}}" class="font-weight-bold date-text">{{sprintf('%02d', $i)}}</td>'+
    @endfor
                        '<td style="width:12mm;  font-size:3mm;" class="text-center date-text font-weight-bold">&nbsp;</td>'+
                    '</tr>';

    var footerPage ='<div class="w-100 mt-2">'+
                        '<div class="row g-3">'+
                            '<div class="col-4"><div class="signature-box"><div class="signature-title">Prepared By</div><div class="signature-line"></div><div class="signature-label">Name / Signature</div></div></div>'+
                            '<div class="col-4"><div class="signature-box"><div class="signature-title">Checked By</div><div class="signature-line"></div><div class="signature-label">Name / Signature</div></div></div>'+
                            '<div class="col-4"><div class="signature-box"><div class="signature-title">Approved By</div><div class="signature-line"></div><div class="signature-label">Name / Signature</div></div></div>'+
                        '</div>'+
                    '</div>';

    var pageTable = '<section class="sheet padding-5mm">'+
    '    <article class="w-100">'+
    pageHeader+
    '        <div class="table-wala mt-2 w-100">'+
    '            <table class="table-hajiri table table-bordered border-dark">'+
    tableHeader+'</table>'+
    '        </div>'+
    footerPage+
    '    </article>'+
    '</section>';


    @php /*de
    var tableUserData = ' '+
    @foreach($users as $user)
                    '<tr  class="d-flex hajiri-data-table" id="tr-user-{{$user->device_id}}">'+
                        '<td style="width:40mm; font-size:3mm;">{{$user["name"]}}</td>'+
                        '<td style="width:230mm; font-size:3mm;">Loading Data for Employee: <b>{{$user->name}}</b> : {{$user->device_id}}</td>'+
                    '</tr>'+
    @endforeach
                    ' ';
                */ @endphp



    countUser.registerListener(function(val) {
        $('#numOfLoadedUser').text(val);
        if({{count($users)}} == countUser.a){
            var $section = $(pageTable);
            $('.hajiri-data-table').each(function(k,v){
                $section.children().children().siblings('.table-wala').children().children().append($(v));
            });
            $(document.body).append($section);
            $('#splashPage').addClass('d-none');
        }
    });


    @foreach($users as $user)
        ajax(@json((string) $user->device_id));
    @endforeach



    function ajax(device_id, attempt)
    {
        attempt = attempt || 1;
        var data = {
            'device_id':device_id,
            'year': {{$nowData['yearBS']}} ,
            'month':{{$nowData['monthBS']}}
        };

        $.ajax({
            type: "GET",
            url: '{{route('hajiri.report.fetch')}}',
            data: data,
            success: function (result) {
                if (result.status)
                {
                    countUser.a = countUser.a + 1;
                    $('#tr-user-'+device_id+' td:nth-child(2)').remove();
                    if(1){
                        $.each(result.data, function( index, value ) {
                            var inInvalid = value.in !== '-' && value.in_valid === false;
                            var outInvalid = value.out !== '-' && value.out_valid === false;
                            var inClass = inInvalid ? ' text-dark fw-bold' : '';
                            var outClass = outInvalid ? ' text-dark fw-bold' : '';
                            if(value.holiday != null)
                            {
                                 var valueOutFont='2.5mm';
                                    var valueInFont='2.5mm';
                                var size_ = (1)?'2mm':'2.5mm';
                                // $('#tr-user-'+device_id).append('<td id="hajiri_'+device_id+'_'+index.replaceAll('-','_')+'" style="width:7mm; font-size:'+size_+';"><div style="margin-top:7.5mm; padding:1mm;" class="w-100 date_90  text-left"><div style="line-height:1;" class="w-100">'+value.holiday+'</div></div></td>');
                                //   $('#tr-user-'+device_id).append('<td style="width:7mm;"><div style=" font-size:'+valueInFont+'; margin-top:4mm; padding:1mm;" class="w-100 date_90  text-left"><div class="w-100">'+value.in+'</div><div  class="w-100" style="font-size:'+valueOutFont+';margin-top: -5px;">'+value.out+'</div></div></td>');
                                if(value.in =='-' && value.out == '-' )
                                {
                                    var size_ = (value.holiday.length > 1)?'1.5mm':'2mm';
                                    // var size_ = (1)?'2mm':'2.5mm';
                                    $('#tr-user-'+device_id).append('<td style="width:7mm; font-size:'+size_+';"><div style="margin-top:7.5mm; padding:1mm;" class="w-100 date_90  text-left"><div style="line-height:1; width: 10mm !important;margin-top: -0.8mm; margin-left: -2mm;" class="w-100 text-center text-danger">'+value.holiday+'</div></div></td>');
                                }
                                else
                                {

                                    var valueOutFont='2mm';
                                    var valueInFont='2mm';
                                    $flagHoli = false;
                                    if(value.in == '-'){
                                        value.in = ""; value.out = value.holiday; valueInFont='1.5mm'; $flagHoli = true;
                                        inClass = ''; outClass = '';
                                    }
                                    else if(value.out == '-'){
                                        value.out = value.holiday; valueOutFont='1.5mm'; $flagHoli = true;
                                        outClass = '';
                                    }
                                    if(!$flagHoli){
                                        value.in = value.in + "~" + value.out;
                                        value.out = value.holiday;
                                        valueInFont='1.6mm';
                                        valueOutFont='1.5mm';
                                        inClass = (inInvalid || outInvalid) ? ' text-dark fw-bold' : '';
                                        outClass = '';
                                    }
                                    $('#tr-user-'+device_id).append('<td style="width:7mm;"><div style="margin-top:4mm; padding:1mm;" class="w-100 date_90  text-left"><div class="w-100'+inClass+'" style="font-size:'+valueOutFont+';width: 12mm !important;margin-top: -0.8mm; margin-left: -3mm;">'+value.in+'</div><div  class="w-100'+outClass+'" style="font-size:'+valueOutFont+';width: 10mm !important;margin-top: -0.5mm; margin-left: -3mm;">'+value.out+'</div></div></td>');
                                }
                            }
                            else{
                                 if(value.in =='-' && value.out == '-'){
                                    var size_ = (1)?'2mm':'2.5mm';
                                    $('#tr-user-'+device_id).append('<td id="hajiri_'+device_id+'_'+index.replaceAll('-','_')+'" style="width:7mm; font-size:'+size_+';"><div style="margin-top:7.5mm; padding:1mm;" class="w-100 date_90  text-left"><div style="line-height:1; width: 10mm !important;margin-top: -0.8mm; margin-left: -2mm;" class="w-100 text-center">Absent</div></div></td>');
                                 }
                                 else
                                 {
                                     $('#tr-user-'+device_id).append('<td  id="hajiri_'+device_id+'_'+index.replaceAll('-','_')+'" style="width:7mm; font-size:2.5mm;"><div style="margin-top:4mm; padding:1mm;" class="w-100 date_90  text-left"><div class="w-100'+inClass+'">'+value.in+'</div><div  class="w-100'+outClass+'" style="margin-top: -5px;">'+value.out+'</div></div></td>');
                                 }

                            }
                        });
                        // $('#tr-user-'+device_id).append('<td style="width:12mm;  font-size:3mm; line-height:3mm;" class="text-left  font-weight-bold">P:'+result.aako+'<br>D:'+result.dsa+'<br>H:'+result.holiday+'</td>');
                        $('#tr-user-'+device_id).append('<td style="width:12mm;  font-size:3mm; line-height:3mm;" class="text-left  font-weight-bold">'+result.dsa+'</td>');
                        xlsxDSA[device_id][2] = result.dsa;

                        $.each(result.dsaTF,function (index,value){
                            if(value == false)
                            {
                                $('#hajiri_'+device_id+'_'+index.replaceAll('-','_')).children('div').addClass('fw-bold');
                            }
                        });
                        // D:'+result.dsa+'<br>P: '+result.length+
                        syncNepali();
                    }
                    else{
                        $('#tr-user-'+device_id).remove();
                    }
                }
                else
                {

                    return false;
                }
            },
            error: function (jqXHR, exception) {
                if (attempt < 3) {
                    ajax(device_id, attempt + 1);
                    return;
                }

                $('#numOfLoadedUser').closest('.h3')
                    .removeClass('h3')
                    .addClass('text-danger fw-bold')
                    .text('Unable to load attendance data. Please refresh or check login/session.');
            }
        });
    }
});

let wb;
function download()
{
    wb = XLSX.utils.book_new();

    wb.Props = {
        Title: 'Attendance Report',
        Subject: 'Attendance Report',
        Author: 'School',
        CreatedDate: new Date(),
    };

    let wsName = 'newSheet';

    var newDSA = [];
    var count = 0;
    xlsxDSA.forEach((v,k)=>{
        newDSA[count] = v;
        count++;
    });
    let wsData =  newDSA;

    let ws = XLSX.utils.aoa_to_sheet(wsData);
    XLSX.utils.book_append_sheet(wb, ws, wsName);
    XLSX.writeFile(wb, 'attendance-{{$nowData['yearBS']}}-{{$nowData['monthBS']}}.xlsx');

}

syncNepali();

function syncNepali(){
    $('.date-text').each((k,v)=>{
        $(v).html(translateNumerals($(v).html(),'devanagari'));
    });
}

function translateNumerals(input, target) {
    var systems = {
            devanagari: 2406, tamil: 3046, kannada:  3302,
            telugu: 3174, marathi: 2406, malayalam: 3430,
            oriya: 2918, gurmukhi: 2662, nagari: 2534, gujarati: 2790
        },
        zero = 48, // char code for Arabic zero
        nine = 57, // char code for Arabic nine
        offset = (systems[target.toLowerCase()] || zero) - zero,
        output = input.toString().split(""),
        i, l = output.length, cc;

    for (i = 0; i < l; i++) {
        cc = output[i].charCodeAt(0);
        if (cc >= zero && cc <= nine) {
            output[i] = String.fromCharCode(cc + offset);
        }
    }
    return output.join("");
}
</script>
</html>
