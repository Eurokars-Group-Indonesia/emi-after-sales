<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
        DROP PROCEDURE IF EXISTS sp_generateReportRetention;

        CREATE PROCEDURE sp_generateReportRetention(
            IN p_kdDealer JSON,
            IN p_tahun INT,
            IN p_categoryCustomer VARCHAR(50),
            IN p_kdModel JSON,
            IN p_uio VARCHAR(50),
            IN p_includingVin VARCHAR(50)
        )
        BEGIN
            DECLARE v_totalUio INT DEFAULT 0;
            DECLARE v_totalGap INT DEFAULT 0;
            DECLARE v_totalGapCount INT DEFAULT 0;

            DECLARE v_fromDate DATE;
            DECLARE v_toDate DATE;
            DECLARE v_totalCustomerVisit INT;

            DECLARE v_i INT DEFAULT 1;
            DECLARE v_totalKolomBulan INT DEFAULT 12;
            DECLARE v_rangePeriodeBulan VARCHAR(100);

            DECLARE v_startPeriodeCol DATE;
            DECLARE v_bulanStr VARCHAR(50);
            DECLARE v_startCustVisit DATE;
            DECLARE v_endCustVisit DATE;

            DECLARE v_currentDate DATE;

            DECLARE v_nilaiUio INT DEFAULT 0;
            DECLARE v_overallUio INT;

            DROP TEMPORARY TABLE IF EXISTS temp_customerVisit;
            DROP TEMPORARY TABLE IF EXISTS temp_reportretention;
            DROP TEMPORARY TABLE IF EXISTS temp_detailCustomerVisit;
            DROP TEMPORARY TABLE IF EXISTS temp_detailFaktur;
            DROP TEMPORARY TABLE IF EXISTS temp_detailGap;

            SELECT nilai, overall 
            INTO v_nilaiUio, v_overallUio
            FROM tbluio
            WHERE kd_uio = p_uio;

            CREATE TEMPORARY TABLE temp_reportretention (
                rangePeriode VARCHAR(100),
                bulan VARCHAR(50), 
                customer_visit INT, 
                uio INT, 
                service_retention DECIMAL(10,2),
                gap INT
            );

            CREATE TEMPORARY TABLE temp_detailCustomerVisit (
                rangePeriode VARCHAR(100),
                no_vin VARCHAR(255),
                dealer_sold VARCHAR(255),
                tanggal_service DATE,
                dealer_service VARCHAR(255),
                category_1 VARCHAR(255),
                permintaan_pelanggan TEXT
            );

            CREATE TEMPORARY TABLE temp_detailFaktur (
                periode VARCHAR(100),
                tanggal_faktur DATE,
                fk_model INT, 
                nm_model VARCHAR(100),
                fk_dealer INT, 
                nm_dealer VARCHAR(255),
                fk_vin VARCHAR(255)
            );

            CREATE TEMPORARY TABLE temp_detailGap (
                rangePeriode VARCHAR(100),
                fk_vin VARCHAR(255),
                tanggal_faktur DATE,
                fk_model INT, 
                nm_model VARCHAR(100),
                fk_dealer INT, 
                nm_dealer VARCHAR(255)
            );

            SET v_currentDate = MAKEDATE(p_tahun, DAYOFYEAR(CURDATE()));
            SET v_startPeriodeCol := DATE_FORMAT(DATE_SUB(v_currentDate, INTERVAL 11 MONTH), '%Y-%m-01');

            SET v_startCustVisit := v_startPeriodeCol;
            SET v_endCustVisit := v_currentDate;

            CREATE TEMPORARY TABLE temp_customerVisit AS
            SELECT DISTINCT
                tc.no_vin,
                td_sold.nm_dealer AS dealer_sold,
                tblsub_kpi.tanggal_service,
                td_service.nm_dealer AS dealer_service,
                tcv.nm_category_vehicles,
                tblsub_kpi.customer_request
            FROM (
                SELECT 
                    t.fk_customer,
                    t.fk_category_1_vehicles,
                    t.customer_request,
                    t.tanggal_faktur AS tanggal_service
                FROM tblkpi t
                JOIN (
                    SELECT fk_customer, fk_category_1_vehicles, MAX(tanggal_faktur) AS max_tanggal
                    FROM tblkpi
                    WHERE tanggal_faktur >= CONCAT(v_startCustVisit, ' 00:00:00')
                      AND tanggal_faktur <= CONCAT(v_endCustVisit, ' 23:59:59')
                    GROUP BY fk_customer, fk_category_1_vehicles
                ) x ON t.fk_customer = x.fk_customer 
                    AND t.fk_category_1_vehicles = x.fk_category_1_vehicles 
                    AND t.tanggal_faktur = x.max_tanggal
                WHERE tanggal_faktur >= CONCAT(v_startCustVisit, ' 00:00:00')
                  AND tanggal_faktur <= CONCAT(v_endCustVisit, ' 23:59:59')
            ) tblsub_kpi
            JOIN tblcustomer tc ON tc.kd_customer = tblsub_kpi.fk_customer
            JOIN tblfaktur tf ON tf.fk_vin = tc.no_vin 
            JOIN tbldealer td_sold ON td_sold.kd_dealer = tf.fk_dealer
            JOIN tbldealer td_service ON td_service.kd_dealer = tc.fk_dealer
            LEFT JOIN tblcategory_vehicles tcv ON tcv.kd_category_vehicles = tblsub_kpi.fk_category_1_vehicles
            WHERE tc.fk_model IN (
                SELECT kdModel FROM JSON_TABLE(p_kdModel, '$[*]' COLUMNS (kdModel VARCHAR(50) PATH '$')) jt
            )
            AND tc.fk_dealer IN (
                SELECT kdDealer FROM JSON_TABLE(p_kdDealer, '$[*]' COLUMNS (kdDealer VARCHAR(50) PATH '$')) jt
            );

            WHILE v_i <= v_totalKolomBulan DO

                SET v_totalUio := 0;
                SET v_totalCustomerVisit := 0;
                SET v_bulanStr := CONCAT(DATE_FORMAT(v_startPeriodeCol, '%b'), '-', YEAR(v_startPeriodeCol));

                SET v_fromDate = v_startPeriodeCol;
                SET v_toDate = LAST_DAY(v_startPeriodeCol);

                SET v_rangePeriodeBulan = CONCAT(
                    DATE_FORMAT(v_fromDate, '%m/%d/%y'),
                    ' - ',
                    DATE_FORMAT(v_toDate, '%m/%d/%y')
                );

                SELECT COUNT(*) INTO v_totalCustomerVisit
                FROM temp_customerVisit
                WHERE tanggal_service >= v_fromDate
                  AND tanggal_service <= v_toDate;

                INSERT INTO temp_reportretention
                VALUES(
                    v_rangePeriodeBulan,
                    v_bulanStr,
                    v_totalCustomerVisit,
                    v_totalUio,
                    COALESCE(v_totalCustomerVisit,0) / NULLIF(v_totalUio,0) * 100,
                    v_totalGapCount
                );

                SET v_startPeriodeCol := v_startPeriodeCol + INTERVAL 1 MONTH;
                SET v_i := v_i + 1;

            END WHILE;

            SELECT * FROM temp_reportretention;

            DROP TEMPORARY TABLE IF EXISTS temp_reportretention;
            DROP TEMPORARY TABLE IF EXISTS temp_detailCustomerVisit;
            DROP TEMPORARY TABLE IF EXISTS temp_detailFaktur;
            DROP TEMPORARY TABLE IF EXISTS temp_customerVisit;
            DROP TEMPORARY TABLE IF EXISTS temp_detailGap;

        END
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_generateReportRetention");
    }
};