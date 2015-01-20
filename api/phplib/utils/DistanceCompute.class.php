<?php

class DistanceComputer
{
	const EARTH_RADIUS = 6371.01; // 地球平均半径，单位：km
	
	private static $instance = NULL;
	
    protected function __construct()
    {}
    
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new DistanceCompute();
		}
		return self::$instance;
	}
	
	/*
	 * 计算两点间的距离，输入两点的经纬度信息, 结果单位为: km
	 */
	public function get_distance($latitude1, $longitude1, $latitude2, $longitude2)
	{
		$radius_latitude1 = $this->_get_angular_radius($latitude1);
		$radius_latitude2 = $this->_get_angular_radius($latitude2);
		
		$radius_longitude1 = $this->_get_angular_radius($longitude1);
		$radius_longitude2 = $this->_get_angular_radius($longitude2);
		
		$delta_radius_latitude = $radius_latitude1 - $radius_latitude2;
		$delta_radius_longitude = $radius_longitude1 - $radius_longitude2;
		
		$temp = 2 * asin(sqrt(pow(sin($delta_radius_latitude / 2), 2) + 
			 cos($radius_latitude1) * cos($radius_latitude2) * pow(sin($delta_radius_longitude/2),2)));
		
		$temp = $temp * self::EARTH_RADIUS;
		$distance = round($temp * 10000) / 10000;
		
		return $distance;
	}

	/*
	 * 计算当前位置的矩形范围
	 * $longitude：经度 
	 * $latitude：纬度 
	 * $distance：距离，单位: km
	 */
	public function get_bound($latitude, $longitude, $distance)
	{
		// 先把经纬度换算成角半径
		$radius_latitude = $this->_get_angular_radius($latitude);
		$radius_longitude = $this->_get_angular_radius($longitude);
		
		$radius_distance = doubleval($distance / self::EARTH_RADIUS);
		
		// 先计算纬度的范围
		$min_latitude = doubleval($radius_latitude - $radius_distance);
		$max_latitude = doubleval($radius_latitude + $radius_distance);
		
		// 初始化经度范围的变量
		$min_longitude = doubleval(0);
		$max_longitude = doubleval(0);

		if (($min_latitude > -M_PI/2) && ($max_latitude < M_PI/2))
		{
			$delta_longitude = doubleval(asin(sin($radius_distance) / cos($radius_latitude)));
			$min_longitude = $radius_longitude - $delta_longitude;
			
			if ($min_longitude < -M_PI)
			{
				$min_longitude += 2 * M_PI;
			}
			
			$max_longitude = $radius_longitude + $delta_longitude;
			if ($max_longitude > M_PI) 
			{
				$max_longitude -= 2 * M_PI;
			}
		}
		else
		{
			$min_latitude = max($min_latitude, -M_PI/2);
			$max_latitude = min($max_latitude, M_PI/2);
			$min_longitude =  -M_PI;
			$max_longitude = M_PI;
		}
		
		// 把角半径换成经纬度
		$min_latitude = $this->_get_degree($min_latitude);
		$max_latitude = $this->_get_degree($max_latitude);
		$min_longitude = $this->_get_degree($min_longitude);
		$max_longitude = $this->_get_degree($max_longitude);
		
		$arr_ret = array(
			'min_latitude' => $min_latitude,
			'max_latitude' => $max_latitude,
			'min_longitude' => $min_longitude,
			'max_longitude' => $max_longitude,
		);

		return $arr_ret;
	}
	
	// 计算角半径
	private function _get_angular_radius($degree)
	{
		return doubleval($degree * M_PI / 180.0);
	}
	
	// 计算角度
	private function _get_degree($radius)
	{
		return doubleval($radius * 180 / M_PI);
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
?>
