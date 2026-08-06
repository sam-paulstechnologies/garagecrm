-- UNEXECUTED PLAN ONLY. Do not run as part of baseline creation or staging provisioning.
-- Production execution requires separate approval after local and staging validation.
-- Run with an identity authorized for these two views only and retain the prior approved
-- view release as the rollback artifact. No fixed DEFINER is permitted.

-- PRE-DEPLOYMENT READ-ONLY DEPENDENCY CHECKS
SELECT TABLE_NAME, TABLE_TYPE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('message_logs', 'journeys', 'journey_enrollments', 'leads', 'opportunities')
ORDER BY TABLE_NAME;

SELECT TABLE_NAME, COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND (
    (TABLE_NAME = 'message_logs' AND COLUMN_NAME IN ('created_at','company_id','source','ai_confidence','escalation_reason')) OR
    (TABLE_NAME = 'journeys' AND COLUMN_NAME IN ('id','company_id','name')) OR
    (TABLE_NAME = 'journey_enrollments' AND COLUMN_NAME IN ('id','company_id','journey_id','enrollable_type','enrollable_id')) OR
    (TABLE_NAME = 'leads' AND COLUMN_NAME IN ('id','company_id')) OR
    (TABLE_NAME = 'opportunities' AND COLUMN_NAME IN ('id','company_id','lead_id','stage'))
  )
ORDER BY TABLE_NAME, COLUMN_NAME;

-- FORWARD: IDEMPOTENT REPLACEMENT
CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_ai_metrics_daily` AS
SELECT
  CAST(`ml`.`created_at` AS DATE) AS `report_date`,
  `ml`.`company_id` AS `company_id`,
  SUM(CASE WHEN `ml`.`source` = 'ai' THEN 1 ELSE 0 END) AS `ai_count`,
  SUM(CASE WHEN `ml`.`source` = 'template' THEN 1 ELSE 0 END) AS `template_count`,
  SUM(CASE WHEN `ml`.`source` = 'human' THEN 1 ELSE 0 END) AS `human_count`,
  ROUND(AVG(`ml`.`ai_confidence`), 2) AS `avg_confidence`,
  SUM(CASE WHEN `ml`.`escalation_reason` IS NOT NULL THEN 1 ELSE 0 END) AS `alerts_count`
FROM `message_logs` AS `ml`
GROUP BY CAST(`ml`.`created_at` AS DATE), `ml`.`company_id`;

CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_journey_summary` AS
SELECT
  `j`.`id` AS `journey_id`,
  `j`.`company_id` AS `company_id`,
  `j`.`name` AS `journey_name`,
  COUNT(DISTINCT `je`.`id`) AS `total_enrollments`,
  COUNT(DISTINCT CASE WHEN `je`.`enrollable_type` = 'App\\Models\\Client\\Lead' THEN `je`.`enrollable_id` END) AS `total_leads`,
  COUNT(DISTINCT CASE WHEN `o`.`id` IS NOT NULL THEN `o`.`id` END) AS `total_opportunities`,
  COUNT(DISTINCT CASE WHEN `o`.`stage` = 'booking_confirmed' THEN `o`.`id` END) AS `total_closed_won`
FROM `journeys` AS `j`
LEFT JOIN `journey_enrollments` AS `je`
  ON `je`.`journey_id` = `j`.`id` AND `je`.`company_id` = `j`.`company_id`
LEFT JOIN `leads` AS `l`
  ON `l`.`id` = `je`.`enrollable_id`
 AND `je`.`enrollable_type` = 'App\\Models\\Client\\Lead'
 AND `l`.`company_id` = `j`.`company_id`
LEFT JOIN `opportunities` AS `o`
  ON `o`.`lead_id` = `l`.`id` AND `o`.`company_id` = `j`.`company_id`
GROUP BY `j`.`id`, `j`.`company_id`, `j`.`name`;

-- POST-DEPLOYMENT RESULT-METADATA VERIFICATION; RETURNS NO APPLICATION ROWS.
SELECT * FROM `vw_ai_metrics_daily` WHERE 1 = 0;
SELECT * FROM `vw_journey_summary` WHERE 1 = 0;

-- ROLLBACK SQL
-- This deliberately does not restore the stale fixed DEFINER. It restores the recovered
-- production query semantics for journey wins while retaining safe INVOKER execution.
CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_ai_metrics_daily` AS
SELECT
  CAST(`ml`.`created_at` AS DATE) AS `report_date`,
  `ml`.`company_id` AS `company_id`,
  SUM(CASE WHEN `ml`.`source` = 'ai' THEN 1 ELSE 0 END) AS `ai_count`,
  SUM(CASE WHEN `ml`.`source` = 'template' THEN 1 ELSE 0 END) AS `template_count`,
  SUM(CASE WHEN `ml`.`source` = 'human' THEN 1 ELSE 0 END) AS `human_count`,
  ROUND(AVG(`ml`.`ai_confidence`), 2) AS `avg_confidence`,
  SUM(CASE WHEN `ml`.`escalation_reason` IS NOT NULL THEN 1 ELSE 0 END) AS `alerts_count`
FROM `message_logs` AS `ml`
GROUP BY CAST(`ml`.`created_at` AS DATE), `ml`.`company_id`;

CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `vw_journey_summary` AS
SELECT
  `j`.`id` AS `journey_id`,
  `j`.`company_id` AS `company_id`,
  `j`.`name` AS `journey_name`,
  COUNT(DISTINCT `je`.`id`) AS `total_enrollments`,
  COUNT(DISTINCT CASE WHEN `je`.`enrollable_type` = 'App\\Models\\Client\\Lead' THEN `je`.`enrollable_id` END) AS `total_leads`,
  COUNT(DISTINCT CASE WHEN `o`.`id` IS NOT NULL THEN `o`.`id` END) AS `total_opportunities`,
  COUNT(DISTINCT CASE WHEN `o`.`stage` = 'closed_won' THEN `o`.`id` END) AS `total_closed_won`
FROM `journeys` AS `j`
LEFT JOIN `journey_enrollments` AS `je`
  ON `je`.`journey_id` = `j`.`id` AND `je`.`company_id` = `j`.`company_id`
LEFT JOIN `leads` AS `l`
  ON `l`.`id` = `je`.`enrollable_id`
 AND `je`.`enrollable_type` = 'App\\Models\\Client\\Lead'
 AND `l`.`company_id` = `j`.`company_id`
LEFT JOIN `opportunities` AS `o`
  ON `o`.`lead_id` = `l`.`id` AND `o`.`company_id` = `j`.`company_id`
GROUP BY `j`.`id`, `j`.`company_id`, `j`.`name`;

-- The AI rollback is textually the same because the forward change only removes the
-- unsafe execution identity; the recovered SELECT semantics did not require correction.
