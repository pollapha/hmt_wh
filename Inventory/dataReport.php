<?php

function select_group_onhand_item($mysqli)
{
	$sql = "WITH a AS (
	SELECT
	supplier_code, document_date receive_date, 
	t1.invoice_no, part_no, part_name, 
	t1.pallet_no, t1.certificate_no, t1.case_tag_no, 
	SUM(t2.qty) total_qty, 
	ROUND(SUM(t2.net_per_pcs)) total_net_per_pallet,
	tod.fg_tag_no,
	if(tod.fg_tag_no IS NOT NULL, 'Yes', 'No') repack_process,  
	location_code, location_area
	FROM hmt_wh.tbl_inventory t1
	left join tbl_inventory_detail t2 ON t1.inventory_id = t2.inventory_id
	left join tbl_order tod ON t2.order_id = tod.order_id
	left join tbl_part_master t3 ON t1.part_id = t3.part_id
	left join tbl_location_master t4 ON t2.location_id = t4.location_id
	left join tbl_supplier_master t5 ON t3.supplier_id = t5.supplier_id
	left join tbl_transaction_line t6 ON t1.transaction_line_id = t6.transaction_line_id
	left join tbl_transaction t7 ON t6.transaction_id = t7.transaction_id
	WHERE t2.package_no IS NULL
	group by t1.case_tag_no, tod.fg_tag_no
	order by receive_date, t1.case_tag_no, tod.fg_tag_no)
	SELECT row_number() over (order by receive_date, case_tag_no, fg_tag_no) row_num, 
	supplier_code, receive_date, invoice_no, part_no, part_name, pallet_no, certificate_no, case_tag_no, 
	total_net_per_pallet, total_qty, fg_tag_no, repack_process, 
	location_code, location_area
	FROM a;";
	// exit($sql);
	return $sql;
};

function select_group_onhand_part($mysqli)
{
	$sql = "WITH a AS (
	SELECT supplier_code, part_no, part_name, t1.qty, t1.net_per_pcs, t1.package_no
	FROM hmt_wh.tbl_inventory_detail t1
	left join tbl_part_master t3 ON t1.part_id = t3.part_id
	left join tbl_location_master t4 ON t1.location_id = t4.location_id
	left join tbl_supplier_master t5 ON t3.supplier_id = t5.supplier_id
	order by supplier_code, part_no)
	SELECT row_number() over (order by part_no) row_num, 
	supplier_code, part_no, part_name, sum(net_per_pcs) total_net_per_pallet, sum(qty) total_qty FROM a WHERE package_no IS NULL
	GROUP BY part_no;";
	return $sql;
};

function select_group_billing($mysqli, $data)
{
	try {
		$where = [];

		if ($data['start_date'] == '' && $data['stop_date'] == '') {
			$sqlWhere = '';
		} else if ($data['start_date'] != '' && $data['stop_date'] == '') {
			$sqlWhere = '';
			throw new Exception('กรุณาป้อนวันที่สิ้นสุด');
		} else if ($data['start_date'] == '' && $data['stop_date'] != '') {
			throw new Exception('กรุณาป้อนวันที่เริ่มต้น');
			$sqlWhere = '';
		} else {
			$where[] = "AND DATE(t1.delivery_date) between DATE('$data[start_date]') and DATE('$data[stop_date]')";
		}

		$sqlWhere = join(' and ', $where);
	} catch (Exception $e) {
		$mysqli->rollback();
		closeDBT($mysqli, 2, $e->getMessage());
	}

	$sql = "SELECT 
		ROW_NUMBER() OVER (order by document_date DESC, document_no DESC, transaction_line_id ASC) row_num,
		document_no, t1.document_date, t2.invoice_no, supplier_code, t1.delivery_date,
		order_no, dos_no, pallet_no, certificate_no, case_tag_no, fg_tag_no, part_no, part_name,
		net_per_pallet, qty
	FROM
		tbl_transaction t1
			INNER JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
			INNER JOIN tbl_part_master t3 ON t2.part_id = t3.part_id
			LEFT JOIN tbl_location_master t4 ON t2.from_location_id = t4.location_id
			LEFT JOIN tbl_location_master t5 ON t2.to_location_id = t5.location_id
			LEFT JOIN tbl_order_header t6 ON t1.order_header_id = t6.order_header_id
			LEFT JOIN tbl_supplier_master t7 ON t3.supplier_id = t7.supplier_id
	WHERE (t1.transaction_type = 'Picking' OR t1.transaction_type = 'Out')
		AND t2.status = 'Complete'
		$sqlWhere
	order by document_date DESC, document_no DESC, transaction_line_id ASC;";
	return $sql;
};

function select_group_onhand_package($mysqli)
{
	$sql = "WITH a AS (
	SELECT package_code package_no, package_type, delivery_status, 
	if(delivery_status = 'In' AND supplier_code IS NULL, 'TTV', supplier_code) supplier_code,
	t1.updated_at, t2.user_fName as updated_by
	FROM tbl_package_master t1
	LEFT JOIN tbl_user t2 ON t1.updated_user_id = t2.user_id
	LEFT JOIN tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
	WHERE t1.status = 'Active')
	SELECT
	row_number() over(ORDER BY package_no ASC) row_num,
	package_no, package_type, delivery_status,
	if(supplier_code = 'TTV',1,0) as ttv,
	if(supplier_code = 'HMTH',1,0) as hmth,
	if(supplier_code = 'NKAPM',1,0) as nkapm,
	updated_at, updated_by
	FROM a;";
	return $sql;
};



function select_group_onhand_steelpipe($mysqli)
{
	$sql = "WITH package_in AS (
SELECT 
	transaction_type, if(isnull(steel_qty),0,sum(steel_qty)) ttv, 0 hmth, 0 nkapm
FROM
	tbl_transaction t1
		LEFT JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
WHERE t1.transaction_type = 'Package In'
	AND t2.status = 'Complete'
	AND package_no IS NOT NULL)
,package_in_2 AS (
SELECT 
	transaction_type, if(isnull(steel_qty),0,sum(steel_qty)) ttv, 0 hmth, 0 nkapm
FROM
	tbl_transaction t1
		LEFT JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
WHERE t1.transaction_type = 'Package In'
	AND t2.status = 'Complete'
	AND package_no IS NOT NULL
    AND t2.supplier_id IS NOT NULL)
,package_onhand_pre AS (
SELECT 
t2.package_no, t2.steel_qty, part_id, status
FROM
tbl_order_header t1
	INNER JOIN tbl_order t2 ON t1.order_header_id = t2.order_header_id
WHERE t1.order_status = 'Picking'
AND t2.status = 'Complete'
GROUP BY t2.package_no
order by t2.package_no)
, package_onhand as (
SELECT 
	t2.package_no,
	if(isnull(t2.steel_qty),0,sum(t2.steel_qty)) ttv, 0 hmth, 0 nkapm
FROM
	package_onhand_pre t2
		INNER JOIN tbl_part_master t4 ON t2.part_id = t4.part_id
		INNER JOIN tbl_supplier_master t5 ON t4.supplier_id = t5.supplier_id)
,package_out_pre AS (
SELECT 
	t1.transaction_id, package_no, steel_qty, t2.part_id, t2.status
FROM
	tbl_transaction t1
		LEFT JOIN tbl_transaction_line t2 ON t1.transaction_id = t2.transaction_id
WHERE t1.transaction_type = 'Out'
	AND t2.status = 'Complete'
	AND package_no IS NOT NULL
GROUP BY package_no ORDER BY t1.transaction_id, package_no)
,package_out AS (
SELECT
	0 ttv,
	if(supplier_code = 'HMTH',if(isnull(steel_qty),0,sum(steel_qty)),0) as hmth,
	if(supplier_code = 'NKAPM',if(isnull(steel_qty),0,sum(steel_qty)),0) as nkapm
FROM
	package_out_pre t2
		INNER JOIN tbl_part_master t3 ON t2.part_id = t3.part_id
		INNER JOIN tbl_supplier_master t4 ON t3.supplier_id = t4.supplier_id)
,sum_total AS (
SELECT 'Qty(pcs.)' as steel_pipe,
(package_in.ttv-package_onhand.ttv)-package_out.hmth as ttv,
package_out.hmth-package_in_2.ttv as hmth,
0 nkapm
FROM package_in, package_onhand, package_out, package_in_2)
SELECT *, ttv+hmth+nkapm as total FROM sum_total;";
	return $sql;
};

function select_group_package_steel($mysqli)
{

	$sql = "WITH a AS (
		SELECT package_code package_no, package_type, delivery_status, 
		if(delivery_status = 'In' AND supplier_code IS NULL, 'TTV', supplier_code) supplier_code
		FROM tbl_package_master t1
		LEFT JOIN tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
		WHERE t1.status = 'Active'
			AND t1.package_type = 'Steel'),
	b AS (
		SELECT
		package_no, package_type, delivery_status,
		if(supplier_code = 'TTV',1,0) as ttv,
		if(supplier_code = 'HMTH',1,0) as hmth,
		if(supplier_code = 'NKAPM',1,0) as nkapm
		FROM a)
	SELECT 'Qty(pcs.)' as steel_pipe, sum(ttv) ttv, sum(hmth) hmth, sum(nkapm) nkapm FROM b;";
	return $sql;
};


function select_group_package_wooden($mysqli)
{
	$sql = "WITH a AS (
		SELECT package_code package_no, package_type, delivery_status, 
		if(delivery_status = 'In' AND supplier_code IS NULL, 'TTV', supplier_code) supplier_code
		FROM tbl_package_master t1
		LEFT JOIN tbl_supplier_master t3 ON t1.supplier_id = t3.supplier_id
		WHERE t1.status = 'Active'
			AND t1.package_type = 'Wooden'),
	b AS (
		SELECT
		package_no, package_type, delivery_status,
		if(supplier_code = 'TTV',1,0) as ttv,
		if(supplier_code = 'HMTH',1,0) as hmth,
		if(supplier_code = 'NKAPM',1,0) as nkapm
		FROM a)
	SELECT 'Qty(pcs.)' as steel_pipe, sum(ttv) ttv, sum(hmth) hmth, sum(nkapm) nkapm FROM b;";
	return $sql;
};
