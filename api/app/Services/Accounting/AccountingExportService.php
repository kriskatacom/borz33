<?php
declare(strict_types=1);
namespace App\Services\Accounting;

use App\Models\Invoice;
use RuntimeException;
use ZipArchive;

final class AccountingExportService
{
    public function __construct(private readonly AccountingService $accounting = new AccountingService()) {}

    public function csv(array $rows): string
    {
        $stream=fopen('php://temp','w+b'); if($stream===false)throw new RuntimeException('Експортът не може да бъде създаден.');
        fwrite($stream,"\xEF\xBB\xBF"); $headers=$this->headers($rows); fputcsv($stream,$headers,';');
        foreach($rows as $row)fputcsv($stream,array_map(fn($h)=>$this->scalar($row[$h]??''),$headers),';');
        rewind($stream); $data=stream_get_contents($stream); fclose($stream); return (string)$data;
    }

    public function xlsx(array $rows, string $sheetName='Справка'): string
    {
        $tmp=tempnam(sys_get_temp_dir(),'accounting-xlsx-'); if($tmp===false)throw new RuntimeException('Не може да се създаде временен файл.');
        $zip=new ZipArchive(); if($zip->open($tmp,ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Excel файлът не може да бъде създаден.');
        $headers=$this->headers($rows); $all=array_merge([$headers],array_map(fn($r)=>array_map(fn($h)=>$r[$h]??'',$headers),$rows)); $sheetRows=[];
        foreach($all as $ri=>$row){$cells=[];foreach(array_values($row)as $ci=>$value){$ref=$this->column($ci+1).($ri+1);if(is_int($value)||is_float($value))$cells[]='<c r="'.$ref.'" t="n"><v>'.$value.'</v></c>';else$cells[]='<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$this->xml($this->scalar($value)).'</t></is></c>';}$sheetRows[]='<row r="'.($ri+1).'">'.implode('',$cells).'</row>';}
        $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.$this->xml(mb_substr($sheetName,0,31)).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml','<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetData>'.implode('',$sheetRows).'</sheetData><autoFilter ref="A1:'.$this->column(max(1,count($headers))).max(1,count($all)).'"/></worksheet>');
        $zip->close(); $data=file_get_contents($tmp); @unlink($tmp); if($data===false)throw new RuntimeException('Excel файлът не може да бъде прочетен.');return $data;
    }

    public function package(string $from,string $to,string $label): string
    {
        $root=dirname(__DIR__,4);$dir=$root.'/storage/accounting/packages';if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir))throw new RuntimeException('Папката за счетоводни пакети не може да бъде създадена.');
        $path=$dir.'/accounting-'.$label.'-'.date('Ymd-His').'.zip';$zip=new ZipArchive();if($zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Счетоводният пакет не може да бъде създаден.');
        foreach(['sales','invoices','credit_notes','payments','refunds','deliveries'] as $type){$report=$this->accounting->report($type,['date_from'=>$from,'date_to'=>$to]);$zip->addFromString('reports/'.$type.'.csv',$this->csv($report['rows']));}
        $dashboard=$this->accounting->dashboard(['date_from'=>$from,'date_to'=>$to]);$summary=[];foreach($dashboard['summary'] as $key=>$value)$summary[]=['metric'=>$key,'value'=>$value];$zip->addFromString('summary.csv',$this->csv($summary));
        $documents=Invoice::query()->whereIn('status',['issued','credited'])->whereBetween('issue_date',[$from,$to])->get();foreach($documents as $doc){$pdf=$doc->pdf_path?$root.'/'.ltrim($doc->pdf_path,'/'):'';if($pdf!==''&&is_file($pdf))$zip->addFile($pdf,($doc->type==='credit_note'?'credit-notes/':'invoices/').basename($pdf));}
        $zip->close();return ltrim(str_replace($root,'',$path),'/');
    }

    private function headers(array $rows): array { return $rows===[]?['info']:array_keys($rows[0]); }
    private function scalar(mixed $v): string { if(is_bool($v))return $v?'Да':'Не';if($v===null)return '';if(is_array($v))return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?:'';return (string)$v; }
    private function xml(string $v): string { return htmlspecialchars($v,ENT_XML1|ENT_QUOTES,'UTF-8'); }
    private function column(int $number): string { $name='';while($number>0){$number--; $name=chr(65+$number%26).$name;$number=intdiv($number,26);}return $name; }
}
