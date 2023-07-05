# peach-consumer-redis

pear让你更畅快地编程。 peach-consumer-redis是以peach-api为基础，增加消费者必要服务，重整为支持Redis队列消费服务的项目。
按模块区分，除了写队列消费者，还可以写一些脚本。

### 前提准备

必要服务支持：php-cli、Redis

可选服务支持：MySQL

### 数据表

```
CREATE TABLE `logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(32) NOT NULL DEFAULT '' COMMENT '级别',
  `log_id` varchar(128) NOT NULL DEFAULT '' COMMENT '事务标识id',
  `data` text COMMENT '数据',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='日志记录';

CREATE TABLE `queue_error` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '类型，1：异步队列，2：延迟队列',
  `queue` varchar(128) NOT NULL DEFAULT '' COMMENT '队列名称',
  `data` text COLLATE utf8mb4_general_ci COMMENT '队列数据',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_queue` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='队列错误记录';
```

### 使用说明

```
cd /yourProjectParentPath

composer create-project peachpear/peach-consumer-redis yourProjectName

cd /path/yourProjectName/

copy .env.example .env
```

### 运行示例
```
cd /path/yourProjectName/

// log队列消费者开始运行
php yii queue/consumer/start log

// mail队列消费者开始运行
php yii queue/consumer/start mail

// ticket队列消费者开始运行
php yii queue/consumer/start ticket

// ticket队列消费者停止运行
php yii queue/consumer/stop ticket

// ticket队列消费者重启运行
php yii queue/consumer/restart ticket

// ticket延迟队列消费者开始运行
php yii queue/delay/start queue
```

#### 特别说明

其实，这个项目中最核心的就是循环从Redis中获取数据那一段代码，完全可以不使用框架。
之所以借用Yii2框架，就是为了方便使用日志功能，日志这一块可以注意下。

#### 目录结构
```
├── common
│   ├── components
│   ├── config
│   ├── dao
│   ├── exception
│   ├── lib
│   ├── misc
│   ├── models
│   └── service
└── console
    ├── components
    ├── config
    └── controllers    
```



#### 编码规范
```
1.PHP所有 关键字 必须 全部小写（常量 true 、false 和 null 也 必须 全部小写）
2.命名model对应的class 必须 以Model结尾
3.命名service对应的class 必须 以Service结尾
4.命名dao对应的class 必须 以Dao结尾
5.数据库查询返回接口 应该 使用model对象/对象列表
6.数据库的key必须是dbname+DB形式，e.g:dbname为test,则key为testDB
7.dao目录存放sql语句或者orm
8.model目录存放对应的数据实例对象
9.service目录存放业务逻辑处理
```