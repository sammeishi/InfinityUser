/*
 Navicat Premium Data Transfer

 Source Server         : DefDev
 Source Server Type    : MySQL
 Source Server Version : 50728
 Source Host           : 192.168.40.133:3306
 Source Schema         : infinityUser

 Target Server Type    : MySQL
 Target Server Version : 50728
 File Encoding         : 65001

 Date: 29/11/2019 18:34:56
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for iu_config
-- ----------------------------
DROP TABLE IF EXISTS `iu_config`;
CREATE TABLE `iu_config`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `space` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `val` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sort` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `space`(`space`, `key`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for iu_login
-- ----------------------------
DROP TABLE IF EXISTS `iu_login`;
CREATE TABLE `iu_login`  (
  `lid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `platform` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pwd` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime(0) DEFAULT NULL,
  `updated_at` datetime(0) DEFAULT NULL,
  PRIMARY KEY (`lid`) USING BTREE,
  INDEX `uid`(`uid`, `platform`, `account`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for iu_profile
-- ----------------------------
DROP TABLE IF EXISTS `iu_profile`;
CREATE TABLE `iu_profile`  (
  `uid` int(11) NOT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '用户显示名称',
  `gender` tinyint(1) DEFAULT NULL COMMENT '性别 0女 1男 null未知',
  `contact` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birthday` datetime(0) DEFAULT NULL COMMENT '生日',
  `avatar_img` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '头像图片 可以使用http',
  `created_at` datetime(0) DEFAULT NULL,
  `updated_at` datetime(0) DEFAULT NULL,
  PRIMARY KEY (`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for iu_user
-- ----------------------------
DROP TABLE IF EXISTS `iu_user`;
CREATE TABLE `iu_user`  (
  `uid` int(20) NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `space` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '所属空间',
  `status` tinyint(4) DEFAULT NULL COMMENT '用户状态',
  `group_id` int(11) DEFAULT NULL COMMENT '所属分组',
  `role_id` int(11) DEFAULT NULL COMMENT '所属角色',
  `created_at` datetime(0) DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime(0) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`uid`) USING BTREE,
  INDEX `space`(`space`, `status`, `group_id`, `role_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
