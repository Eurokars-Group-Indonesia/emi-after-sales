CREATE PROCEDURE `emi_after_sales`.`sp_rpt_retention_report`(
	IN p_kdDealer JSON,
    IN p_tahun INT,
    IN p_categoryCustomer VARCHAR(50),
    IN p_kdModel JSON,
    IN p_uio VARCHAR(50),
    IN p_includingVin VARCHAR(50)

)
begin
	-- report
    declare v_totalUio int default 0;
--     declare v_totalGap int default 0;
    declare v_totalGapCount int default 0;

	-- tanggal periode
	declare v_fromDate date; -- tanggal perkolom start
	declare v_toDate date; -- tanggal perkolom end
	
	declare v_totalCustomerVisit int;
	 
	-- others
	declare v_i int default 1;
	declare v_totalKolomBulan int default 12;
    declare v_rangePeriodeBulan varchar(100);
    
    -- tanggal
    declare v_startPeriodeCol date;
    declare v_bulanStr varchar(50);
    declare v_startCustVisit date;
    declare v_endCustVisit date;
    
    declare v_currentDate date;
    
    -- UIO info
    declare v_nilaiUio int default 0;
    declare v_overallUio int;
    
    
    -- Drop temporary table
    drop table if exists tmp_customerVisitAllPeriode; -- untuk ambil customer visit 1 periode laporan
    
    drop table if exists tmp_stageCustomerVisit; -- stage customer visit
    drop table if exists tmp_stageUio; -- stage uio
    drop table if exists tmp_stageGap; -- stage gap
    
    drop table if exists tmp_detailCustomerVisit; -- detail customer visit
    drop table if exists tmp_detailUio; -- detail uio
    drop table if exists tmp_detailGap; -- detail gap
    
    drop table if exists tmp_finalReportRetention; -- table summary retention report
    
    -- Pembeda overall dan tidak
    select nilai, overall 
    into v_nilaiUio, v_overallUio
    from tbluio
    where kd_uio = p_uio;
    
    
    # TABLE CUSTOMER VISIT =============================================
    create temporary table tmp_detailCustomerVisit (
		rangePeriode varchar(100),
		no_vin varchar(255),
        dealer_sold varchar(255),
        tanggal_service date,
        dealer_service varchar(255),
        category_1 varchar(255),
        permintaan_pelanggan text
	);
    
    create temporary table tmp_stageCustomerVisit (
		rangePeriode varchar(100),
		no_vin varchar(255),
        dealer_sold varchar(255),
        tanggal_service date,
        dealer_service varchar(255),
        category_1 varchar(255),
        permintaan_pelanggan text
	);
    
    
    # TABLE UIO =============================================
    create temporary table tmp_detailUio (
		periode varchar(100),
        tanggal_faktur datetime,
        fk_model int, 
        nm_model nvarchar(100),
		fk_dealer int, 
		nm_dealer varchar(255),
		fk_vin varchar(255)
	);
    
    create temporary table tmp_stageUio (
		periode varchar(100),
        tanggal_faktur datetime,
        fk_model int, 
        nm_model nvarchar(100),
		fk_dealer int, 
		nm_dealer varchar(255),
		fk_vin varchar(255)
	);
    
    # TABLE GAP =============================================
    create temporary table tmp_detailGap (
		rangePeriode varchar(100),
		fk_vin varchar(255),
        tanggal_faktur date,
        fk_model int, 
        nm_model nvarchar(100),
		fk_dealer int, 
		nm_dealer varchar(255),
		tgl_last_service date,
		nm_dealer_service varchar(255)
	);
    
    
    create temporary table tmp_stageGap (
		rangePeriode varchar(100),
		fk_vin varchar(255),
        tanggal_faktur date,
        fk_model int, 
        nm_model nvarchar(100),
		fk_dealer int, 
		nm_dealer varchar(255),
		tgl_last_service date,
		nm_dealer_service varchar(255)
	);
    
    
    
    # TABLE FINAL REPORT RETENTION =============================================
	create temporary table tmp_finalReportRetention (
		rangePeriode varchar(100),
		bulan varchar(50), 
		customer_visit int, 
		uio int, 
		service_retention decimal(10,2),
		gap int
	);
    
    
    # SET UP TANGGAL =============================================
    SET v_currentDate = MAKEDATE(p_tahun, DAYOFYEAR(CURDATE()));
    SET v_startPeriodeCol := date_format(date_sub(v_currentDate, interval 11 month), '%Y-%m-01'); -- v_startPeriodeCol: Mengurangi 12 periode tahun yang dipilih
    
    set v_startCustVisit := v_startPeriodeCol;
    set v_endCustVisit := v_currentDate;
    
    # BUILD tmp_customerVisitAllPeriode =============================================
    -- FIX: Gunakan range eksplisit dengan 23:59:59 agar data datetime seharian ikut terbaca
    CREATE TEMPORARY TABLE tmp_customerVisitAllPeriode
	AS
    select distinct
		tc.no_vin,
        td_sold.nm_dealer as dealer_sold,
		tblsub_kpi.tanggal_service as tanggal_service,
        td_service.nm_dealer as dealer_service,
		tcv.nm_category_vehicles,
		tblsub_kpi.customer_request
	from 
	(
		SELECT 
			t.fk_customer,
			t.fk_category_1_vehicles,
			t.customer_request,
			t.tanggal_faktur AS tanggal_service
		FROM tblkpi t
		JOIN (
			SELECT fk_customer, fk_category_1_vehicles, MAX(tanggal_faktur) AS max_tanggal
			FROM tblkpi
			-- FIX: pakai >= ... AND <= ... + INTERVAL agar tidak ada data yang kelewat karena jam
			WHERE tanggal_faktur >= CONCAT(v_startCustVisit, ' 00:00:00')
			  AND tanggal_faktur <= CONCAT(v_endCustVisit, ' 23:59:59')
			GROUP BY fk_customer, fk_category_1_vehicles
		) x ON t.fk_customer = x.fk_customer 
			AND t.fk_category_1_vehicles = x.fk_category_1_vehicles 
			AND t.tanggal_faktur = x.max_tanggal
		-- FIX: sama seperti subquery di atas
		WHERE tanggal_faktur >= CONCAT(v_startCustVisit, ' 00:00:00')
		  AND tanggal_faktur <= CONCAT(v_endCustVisit, ' 23:59:59')
	) tblsub_kpi
	join tblcustomer tc on tc.kd_customer = tblsub_kpi.fk_customer
	join tblfaktur tf on tf.fk_vin = tc.no_vin 
	join tbldealer td_sold on td_sold.kd_dealer = tf.fk_dealer
	join tbldealer td_service on td_service.kd_dealer = tc.fk_dealer
	left join tblcategory_vehicles tcv on tcv.kd_category_vehicles = tblsub_kpi.fk_category_1_vehicles
    where tc.fk_model in 
	(
		select kdModel
		from JSON_TABLE(
			CAST(p_kdModel AS JSON),
			'$[*]' columns (kdModel varchar(50) path '$')
		) jt
	) 
	and tc.fk_dealer in 
	(
		select kdDealer
		from JSON_TABLE(
			CAST(p_kdDealer AS JSON),
			'$[*]' columns (kdDealer varchar(50) path '$')
		) jt
	)
	AND (
	    p_includingVin = 'yes'
	    OR (
	        p_includingVin = 'no'
	        AND tf.fk_dealer in
	        (
	            select kdDealer
	            from JSON_TABLE(
	                CAST(p_kdDealer AS JSON),
	                '$[*]' columns (kdDealer varchar(50) path '$')
	            ) jt
	        )
	    )
	);
    
	--     select * from tmp_customerVisitAllPeriode;
    
    # ===========================================================================
    # PROSES PENGAMBILAN REPORT =================================================
    # ===========================================================================
	while v_i <= v_totalKolomBulan do
        
		set v_totalUio := 0;
        set v_totalCustomerVisit := 0;
        set v_bulanStr := concat(DATE_FORMAT(v_startPeriodeCol, '%b'), '-', year(v_startPeriodeCol));
        
        -- SETUP v_fromDate & v_toDate
		IF v_overallUio = 0 THEN
        
			IF p_uio = '1styears' AND p_categoryCustomer = 'without' THEN
            
				SET v_fromDate = DATE_FORMAT(
					DATE_ADD(DATE_SUB(v_startPeriodeCol, INTERVAL v_nilaiUio YEAR), INTERVAL 1 MONTH),
					'%Y-%m-01'
				);
				SET v_toDate = LAST_DAY(v_fromDate + INTERVAL 5 MONTH);
                
			ELSE
			
				SET v_fromDate = DATE_FORMAT(
					DATE_ADD(DATE_SUB(v_startPeriodeCol, INTERVAL v_nilaiUio YEAR), INTERVAL 1 MONTH),
					'%Y-%m-01'
				);
				SET v_toDate = LAST_DAY(v_fromDate + INTERVAL 12 MONTH);
            
			END IF;
            
		ELSEIF v_overallUio = 1 THEN
			
            SET v_fromDate = DATE_FORMAT(
				DATE_ADD(DATE_SUB(v_startPeriodeCol, INTERVAL v_nilaiUio YEAR), INTERVAL 1 MONTH),
				'%Y-%m-01'
			);
			SET v_toDate = LAST_DAY(v_fromDate + INTERVAL (12 * v_nilaiUio) MONTH);
            
		END IF;
        
        set v_rangePeriodeBulan = concat(DATE_FORMAT(v_fromDate, '%m/%d/%y'), CHAR(10), ' - ', CHAR(10), DATE_FORMAT(v_toDate, '%m/%d/%y'));
        
        
        
        
    	# GENERATE REPORT =============================================
        
        # UIO ========
        -- FIX: Gunakan >= '...00:00:00' AND <= '...23:59:59' agar data datetime seharian ikut terbaca
        
        -- Get data tmp_stageUio and insert to tmp_detailUio
       	insert into tmp_stageUio (periode, tanggal_faktur, fk_model, nm_model, fk_dealer, nm_dealer, fk_vin)
       	SELECT
		    v_bulanStr AS periode,  tanggal_handover AS tanggal_faktur,  tblfaktur.fk_model,  tblmodel.nm_model,  tblfaktur.fk_dealer, tbldealer.nm_dealer, tblfaktur.fk_vin
		FROM tblfaktur
		JOIN tbldealer ON tbldealer.kd_dealer = tblfaktur.fk_dealer
		JOIN tblmodel ON tblmodel.kd_model = tblfaktur.fk_model
		WHERE tblfaktur.fk_dealer IN 
		(
		    SELECT kdDealer
		    FROM JSON_TABLE(
		        p_kdDealer,
		        '$[*]' COLUMNS (
		            kdDealer VARCHAR(50) PATH '$'
		        )
		    ) jt
		)
		AND tblfaktur.fk_model IN (
		    SELECT kdModel
		    FROM JSON_TABLE(
		        p_kdModel,
		        '$[*]' COLUMNS (
		            kdModel VARCHAR(50) PATH '$'
		        )
		    ) jt
		)
		and status_approval = 'Approved'
		and tanggal_handover >= CONCAT(v_fromDate, ' 00:00:00')
		and tanggal_handover <= CONCAT(v_toDate, ' 23:59:59');
        
        
        ## Insert detail UIO ========
        insert into tmp_detailUio select * from tmp_stageUio;
       	
       	
        ## Insert count tmp_stageUio ========
       	select count(*) 
       		into v_totalUio 
       	from tmp_stageUio
		where tanggal_faktur >= CONCAT(v_fromDate, ' 00:00:00')
		and tanggal_faktur <= CONCAT(v_toDate, ' 23:59:59');
        
        
        # END UIO ========
       	
        
        
       	# CUSTOMER VISIT ========
        
       	## Insert detail tmp_customerVisitAllPeriode
		if p_includingVin = 'no' then 
		
			-- Jika no => 
			-- maka customer visit harus lookup UIO kolom tersebut
			insert into tmp_stageCustomerVisit
			select
			    v_bulanStr as rangePeriode, no_vin, dealer_sold, tanggal_service, dealer_service, nm_category_vehicles, customer_request
			from 
				tmp_customerVisitAllPeriode
			where tanggal_service >= v_startPeriodeCol
			and no_vin in (
				select fk_vin from tmp_stageUio
			)
			and tanggal_service < DATE_ADD(
				DATE_FORMAT(v_startPeriodeCol, '%Y-%m-01'),
			    interval 1 month
			  );
			
		elseif p_includingVin = 'yes' then 
		
			-- Jika yes => 
			-- maka customer visit harus lookup UIO kolom tersebut
			insert into tmp_stageCustomerVisit
			select
			    v_bulanStr as rangePeriode, no_vin, dealer_sold, tanggal_service, dealer_service, nm_category_vehicles, customer_request
			from 
				tmp_customerVisitAllPeriode
			where tanggal_service >= v_startPeriodeCol
		  	and tanggal_service < DATE_ADD(
				DATE_FORMAT(v_startPeriodeCol, '%Y-%m-01'),
			    interval 1 month
			  );
			
		end if;
		
		## Insert detail customer visit
        insert into tmp_detailCustomerVisit select * from tmp_stageCustomerVisit;
		
		## Insert count table tmp_stageUio
       	select count(*) into v_totalCustomerVisit
       	from tmp_stageCustomerVisit
       	where tanggal_service >= v_startPeriodeCol
	  	and tanggal_service < DATE_ADD(
			DATE_FORMAT(v_startPeriodeCol, '%Y-%m-01'),
		    interval 1 month
		  );
		# END CUSTOMER VISIT ========
		
		
		
		# GAP ========
		
		## Insert detail gap dan cari last service diperiode laporan
		insert into tmp_stageGap
		select
		    v_bulanStr as periode, fk_vin, tanggal_faktur, fk_model, nm_model, fk_dealer, nm_dealer, 
		    tempService.tgl_last_service, tempService.dealer_service
	    from  tmp_stageUio uio
		left join (
			select no_vin, dealer_service, max(tanggal_service) as tgl_last_service
			from tmp_customerVisitAllPeriode
			group by no_vin, dealer_service
		) tempService on tempService.no_vin = uio.fk_vin
		-- cari uio yang gak ada di customer visit
		where uio.fk_vin not in (
		    select no_vin from tmp_stageCustomerVisit
		);
		
		
		## Insert to table tmp_detailGap
        insert into tmp_detailGap select * from tmp_stageGap;
		
		
		## Insert count to var v_totalGapCount
		select count(*) into v_totalGapCount from tmp_stageGap;
		
		SET v_totalGapCount = v_totalGapCount;
		
		
-- 		IF v_totalCustomerVisit < v_totalUio THEN 
-- -- 		    set v_totalGapCount = NULLIF(v_totalUio, 0) - COALESCE(v_totalCustomerVisit, 0);
-- 			SET v_totalGapCount = v_totalGapCount;
-- 		ELSE
-- 		    set v_totalGapCount = 0; 
-- 		END IF;
		
		# END GAP ========
		
		
		
		
		
		
		
        
        
        
		# Result insert summary report
		insert into tmp_finalReportRetention (rangePeriode, bulan, customer_visit, uio, service_retention, gap)
		values(
            v_rangePeriodeBulan, 
            v_bulanStr, 
            v_totalCustomerVisit, 
            v_totalUio, 
			COALESCE(v_totalCustomerVisit, 0) / NULLIF(v_totalUio, 0) * 100,
            v_totalGapCount
        );
		
		
		
		
		# REFRESH table temporary stage ========
		truncate table tmp_stageUio;
		truncate table tmp_stageCustomerVisit;
		truncate table tmp_stageGap;
		
		
		

        set v_startPeriodeCol := v_startPeriodeCol + interval 1 month;
		set v_i := v_i + 1;

	end while;
    -- ===================================================================
	
	select * from tmp_finalReportRetention;
	select * from tmp_detailUio;
	select * from tmp_detailCustomerVisit;
	select * from tmp_detailGap;
    
    drop table tmp_stageCustomerVisit;
    drop table tmp_stageUio;
    drop table tmp_stageGap; 
    
   	drop table tmp_customerVisitAllPeriode;
   	drop table tmp_finalReportRetention;
    drop table tmp_detailCustomerVisit; 
    drop table tmp_detailUio; 
    drop table tmp_detailGap; 


end