<?php

namespace App\Helpers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportGenerator
{
    /**
     * Generate a PDF report
     * 
     * @param string $view Blade view path
     * @param array $data Data to pass to the view
     * @param string $filename Filename for the PDF
     * @param array $options Additional PDF options
     * @return \Illuminate\Http\Response
     */
    public static function generatePDF(string $view, array $data, string $filename, array $options = [])
    {
        // Add common data
        $data['generated_at'] = Carbon::now()->format('F d, Y - h:i A');
        $data['generated_by'] = auth()->user()->name ?? 'System';
        
        // Generate PDF
        $pdf = Pdf::loadView($view, $data);
        
        // Apply options
        if (isset($options['orientation'])) {
            $pdf->setPaper('a4', $options['orientation']);
        } else {
            $pdf->setPaper('a4', 'portrait');
        }
        
        // Return for download
        return $pdf->download($filename);
    }
    
    /**
     * Generate a PDF report and store it
     * 
     * @param string $view Blade view path
     * @param array $data Data to pass to the view
     * @param string $path Storage path
     * @return string Path to stored file
     */
    public static function storePDF(string $view, array $data, string $path): string
    {
        $data['generated_at'] = Carbon::now()->format('F d, Y - h:i A');
        $data['generated_by'] = auth()->user()->name ?? 'System';
        
        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('a4', 'portrait');
        
        Storage::put($path, $pdf->output());
        
        return $path;
    }
    
    /**
     * Generate CSV content from array
     * 
     * @param array $headers Column headers
     * @param array $data Data rows
     * @return string CSV content
     */
    public static function generateCSV(array $headers, array $data): string
    {
        $handle = fopen('php://temp', 'r+');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($handle, $headers);
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        
        return $csv;
    }
    
    /**
     * Return CSV as download response
     * 
     * @param string $csv CSV content
     * @param string $filename Filename
     * @return \Illuminate\Http\Response
     */
    public static function downloadCSV(string $csv, string $filename)
    {
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
    
    /**
     * Format currency for display
     */
    public static function formatCurrency($amount): string
    {
        return '₱' . number_format((float)$amount, 2);
    }
    
    /**
     * Generate filename with timestamp
     */
    public static function generateFilename(string $prefix, string $extension = 'pdf'): string
    {
        return 'charityhub_' . $prefix . '_' . Carbon::now()->format('Y-m-d_His') . '.' . $extension;
    }
}
