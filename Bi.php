<?php

class Hao_Bi
{
	static function onlineDate()
	{
		return '20120907';
	}

	static function getFirstAndLastDateOfMonth($month = null)
	{
		$month_time = strtotime(is_null($month) ? date('Y-m') : date('Y-m', strtotime($month)) );
		$first_date = date('Y-m-d', $month_time);
		$last_date = date('Y-m-d', strtotime($first_date . ' +1month -1day'));
		return [$first_date, $last_date];
	}

	static function isTime($str)
	{
		return strlen($str) == 10;
	}

	static function getTimeAreaOfDate($date = null, $end_date = null)
	{
		$begin_time = is_null($date) ? time() : (self::isTime($date) ? $date : strtotime($date));
		$begin = strtotime(date('Y-m-d', $begin_time));

		if (is_null($end_date))
			$end = strtotime(date('Y-m-d 23:59:59', $begin));
		else
			$end = self::isTime($end_date) ? $end_date : strtotime($end_date);

		return [$begin, $end];
	}

	static function whereOfTimeArea($date = null, $end = null)
	{
		if ($date == 'today') {
			return ['between', self::getTimeAreaOfDate()];
		} else if ($date == 'this month') {
			list($first_date, $last_date) = self::getFirstAndLastDateOfMonth();
			return ['between', self::getTimeAreaOfDate($first_date, $last_date)];
		} else {
			return ['between', self::getTimeAreaOfDate($date, $end)];
		}
	}

	static function getValuesOfListByField($list, $field)
	{
		$values = [];
		foreach ($list as $row) {
			$values[] = $row[$field];
		}
		return $values;
	}

	/**
	 * 新增用户数
	 */
	static function newUserCount($date = null, $end = null)
	{
		$M_user = M('user');

		if (is_null($date))
			return $M_user->count();

		$map['addtime'] = self::whereOfTimeArea($date, $end);
		return $M_user->where($map)->count();
	}

	/**
	 * 登录的用户数
	 */
	static function loginUserCount($date = null, $end = null)
	{
		$M_user_login = M('user_login');
		if (is_null($date))
			return $M_user_login->count('distinct uid');

		$map['login_time'] = self::whereOfTimeArea($date, $end);
		return $M_user_login->where($map)->count('distinct uid');
	}

	/**
	 * 充值用户数
	 */
	static function rechargeUserCount($date = null, $end = null)
	{
		$M_recharge = M('recharge');

		$map['status'] = ['in', [
			Hao_Status::$recharge['充值中'], 
			Hao_Status::$recharge['待确认'], 
			Hao_Status::$recharge['充值成功']
		]];

		$date and $map['addtime'] = self::whereOfTimeArea($date, $end);

		return $M_recharge->where($map)->count('distinct uid');
	}

	/**
	 * 充值金额
	 */
	static function rechargeAmount($date = null, $end = null)
	{
		$M_recharge = M('recharge');

		$map['status'] = ['in', [
			Hao_Status::$recharge['充值中'], 
			Hao_Status::$recharge['待确认'], 
			Hao_Status::$recharge['充值成功']
		]];

		$date and $map['addtime'] = self::whereOfTimeArea($date, $end);

		return $M_recharge->where($map)->sum('money');
	}

	/**
	 * 注册且充值的用户
	 */
	static function newUserAndRechargeUserCount($date = null, $end = null)
	{
		$timearea = self::whereOfTimeArea($date, $end);
		$user_map['addtime'] = $timearea;

		$recharge_map['status'] = ['in', [
			Hao_Status::$recharge['充值中'], 
			Hao_Status::$recharge['待确认'], 
			Hao_Status::$recharge['充值成功']
		]];
		$recharge_map['addtime'] = $timearea;

		$user_uids = M('user')->where($user_map)->field('uid')->select();
		$recharge_uids = M('recharge')->where($recharge_map)->distinct(true)->field('uid')->select();

		$user_uids = self::getValuesOfListByField($user_uids, 'uid');
		$recharge_uids = self::getValuesOfListByField($recharge_uids, 'uid');

		$uids = array_intersect($user_uids, $recharge_uids);
		return count($uids);
	}

	/**
	 * 投标用户数
	 */
	static function bidUserCount($date = null, $end = null)
	{
		$M_tender = M('tender');

		$map['status'] = ['in', [
			Hao_Status::$tender['还款中'],
			Hao_Status::$tender['还款成功']
		]];

		$date and $map['bidtime'] = self::whereOfTimeArea($date, $end);

		return $M_tender->where($map)->count('distinct uid');
	}

	/**
	 * 投标金额
	 */
	static function bidAmount($date = null, $end = null)
	{
		$M_tender = M('tender');

		$map['status'] = ['in', [
			Hao_Status::$tender['还款中'],
			Hao_Status::$tender['还款成功']
		]];

		$date and $map['bidtime'] = self::whereOfTimeArea($date, $end);

		return $M_tender->where($map)->sum('money');
	}

	/**
	 * 提现用户
	 */
	static function drawcashUserCount($date = null, $end = null)
	{
		$M_drawcash = M('drawcash');

		$map['status'] = ['egt', Hao_Status::$drawcash['等待处理']];
		$date and $map['addtime'] = self::whereOfTimeArea($date, $end);

		return $M_drawcash->where($map)->count('distinct uid');
	}

	/**
	 * 提现总额
	 */
	static function drawcashAmount($date = null, $end = null)
	{
		$M_drawcash = M('drawcash');

		$map['status'] = ['egt', Hao_Status::$drawcash['等待处理']];
		$date and $map['addtime'] = self::whereOfTimeArea($date, $end);

		return $M_drawcash->where($map)->sum('money');
	}

	/**
	 * arpu
	 */
	static function arpu($date = null)
	{
		$bidUserCount = self::bidUserCount(self::onlineDate(), self::getTimeAreaOfDate($date)[1]);
		if ($bidUserCount < 1)
			return 0;
		$bidAmount = self::bidAmount($date);
		return sprintf('%.2f', $bidAmount/$bidUserCount);
	}

	/**
	 * 充值arpu
	 */
	static function arpuOfRecharge($date = null)
	{
		$date or $date = date('Y-m-d');
		$rechargeUserCount = self::rechargeUserCount(self::onlineDate(), self::getTimeAreaOfDate($date)[1]);
		if ($rechargeUserCount < 1)
			return 0;
		$rechargeAmount = self::rechargeAmount($date);
		return sprintf('%.2f', $rechargeAmount / $rechargeUserCount);
	}

	/**
	 * 注册用户投资 arpu
	 */
	static function arpuOfUserBid($date = null)
	{
		$date or $date = date('Y-m-d');
	   	$newUserCount = self::newUserCount(self::onlineDate(), self::getTimeAreaOfDate($date)[1]);
		if ($newUserCount < 1)
			return 0;
		$bidAmount = self::bidAmount($date);
		return sprintf('%.2f', $arpu);
	}

	/**
	 * 累计运营天数
	 */
	static function daysOfOnline()
	{
		return ceil((time() - strtotime(self::onlineDate())) / 86400);
	}

	/**
	 * 一次性用户数
	 */
	static function singleUserCount()
	{
		$total = self::newUserCount();
		if ($total < 1)
			return 0;
		$single_count = M('user')->where('login_time = 0')->count();
		return sprintf('%.2f', $single_count / $total);
	}

	/**
	 * 注册转换率: 时间范围内新增的注册用户数/第一次启动应用、web访问的用户数
	 */
	static function regConvertRate($date = null, $end = null)
	{
	}

	/**
	 * 充值转换率: 时间范围内新增的注册用户数/第一次启动应用、web访问的用户数
	 */
	static function rechargeConvertRate($date = null, $end = null)
	{
		$newUserAndRechargeUserCount = self::newUserAndRechargeUserCount($date, $end);
		$newUserCount = self::newUserCount($date, $end);

		if ($newUserCount < 1)
			return 0;
		return sprintf('%.2f', $newUserAndRechargeUserCount / $newUserCount);
	}
}
