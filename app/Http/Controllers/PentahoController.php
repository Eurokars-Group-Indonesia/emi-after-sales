<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PentahoController
{

    // ini sudah pasti jalan di CMD
    // "D:\pentaho\pdi-ce-11.0.0.0-237\data-integration\Kitchen.bat" /file:"D:\laragon\www\emi-after-sales\storage\pentaho\job\job_sync_wrs_aftersales.kjb" /param:last_kd_customer="0"  /level:Basic
    // "D:\pentaho\pdi-ce-11.0.0.0-237\data-integration\Kitchen.bat" /file:"D:\laragon\www\emi-after-sales\storage\pentaho\job\job_sync_wrs_aftersales.kjb" /param:last_kd_customer="0" /param:last_kd_kpi="0" /param:last_no_faktur_request="0" /level:Basic
   

    // public function runJob()
    // {
    //     // Path ke Kitchen.bat dan job Pentaho
    //     $kitchenPath = 'D:\\pentaho\\pdi-ce-11.0.0.0-237\\data-integration\\Kitchen.bat';
    //     $jobPath = 'D:\\laragon\\www\\emi-after-sales\\storage\\pentaho\\job\\job_sync_wrs_aftersales.kjb';
        
    //     // Parameter jika perlu
    //     $params = '/param:last_kd_customer=0';
        
    //     // Level logging
    //     $level = '/level:Basic';
        
    //     // Buat command persis sama seperti di CMD yang jalan
    //     $command = [
    //         $kitchenPath,
    //         "/file:{$jobPath}",
    //         $params,
    //         $level
    //     ];
        
    //     $process = new Process($command, 'D:\\pentaho\\pdi-ce-11.0.0.0-237\\data-integration');
        
    //     try {
    //         // Run the process
    //         $process->run();

    //         // Simpan output dan error ke file untuk debug
    //         file_put_contents(storage_path('logs/pentaho_output.log'), $process->getOutput());
    //         file_put_contents(storage_path('logs/pentaho_error.log'), $process->getErrorOutput());

    //         // Cek jika gagal
    //         if (!$process->isSuccessful()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Pentaho job failed',
    //                 'output_log' => storage_path('logs/pentaho_output.log'),
    //                 'error_log' => storage_path('logs/pentaho_error.log')
    //             ]);
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Pentaho job executed',
    //             'output_log' => storage_path('logs/pentaho_output.log'),
    //             'error_log' => storage_path('logs/pentaho_error.log')
    //         ]);

    //     } catch (ProcessFailedException $exception) {
    //         return response()->json([
    //             'status' => 'exception',
    //             'message' => $exception->getMessage(),
    //             'output_log' => storage_path('logs/pentaho_output.log'),
    //             'error_log' => storage_path('logs/pentaho_error.log')
    //         ]);
    //     }
    // }


    

    public function runJob()
    {
        // Path Pentaho Kitchen dan job
        $kitchenPath = 'D:\\pentaho\\pdi-ce-11.0.0.0-237\\data-integration\\Kitchen.bat';
        $jobPath = 'D:\\laragon\\www\\emi-after-sales\\storage\\pentaho\\job\\job_sync_wrs_aftersales.kjb';

        // Parameter job
        $params = '"/param:last_kd_customer=0"';
        $level = '/level:Basic';

        // Environment variables untuk pastikan Java ketemu
        $env = [
            'JAVA_HOME' => 'C:\\Program Files\\Eclipse Adoptium\\jdk-21.0.10.7-hotspot',
            '_PENTAHO_JAVA_HOME' => 'C:\\Program Files\\Eclipse Adoptium\\jdk-21.0.10.7-hotspot',
            '_PENTAHO_JAVA' => 'C:\\Program Files\\Eclipse Adoptium\\jdk-21.0.10.7-hotspot\\bin\\java.exe',
            'PATH' => getenv('PATH') . ';C:\\Program Files\\Eclipse Adoptium\\jdk-21.0.10.7-hotspot\\bin',
        ];

        // Command sebagai array supaya aman untuk spasi di path
        $command = [
            $kitchenPath,
            "/file:$jobPath",
            $params,
            $level
        ];

        // Set working directory ke folder data-integration
        $process = new Process($command, 'D:\\pentaho\\pdi-ce-11.0.0.0-237\\data-integration', $env);

        try {
            $process->setTimeout(300); // 5 menit timeout
            $process->mustRun();

            // Simpan output ke log
            file_put_contents(storage_path('logs/pentaho_output.log'), $process->getOutput());
            file_put_contents(storage_path('logs/pentaho_error.log'), $process->getErrorOutput());

            return response()->json([
                'status' => 'success',
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput()
            ]);

        } catch (ProcessFailedException $exception) {
            // Simpan output error untuk debug
            file_put_contents(storage_path('logs/pentaho_output.log'), $process->getOutput());
            file_put_contents(storage_path('logs/pentaho_error.log'), $process->getErrorOutput());

            return response()->json([
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput()
            ]);
        }
    }



}
