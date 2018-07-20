<?php

/**
 * Handles LTI tables in WordPress
 *
 * @author Richard Lawson <richard.lawson@ed.ac.uk>
 */
class Ed_LTI_Data {

	private $wpdb;

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
	}

	/**
	 * Create table to store the consumers ands passwords if not exists
	 *
	 * @return void
	 */
	public function lti_maybe_create_db() {
		$this->wpdb->ltitable = $this->wpdb->base_prefix . 'lti2_consumer';

		if ( is_user_logged_in() && is_super_admin() ) {
			if ( $this->wpdb->get_var( "SHOW TABLES LIKE '{$this->wpdb->ltitable}'" ) != $this->wpdb->ltitable ) {
				$this->wpdb->query(
					"CREATE TABLE IF NOT EXISTS `{$this->wpdb->ltitable}` (
                      consumer_pk int(11) NOT NULL AUTO_INCREMENT,
                      name varchar(50) NOT NULL,
                      consumer_key256 varchar(256) NOT NULL,
                      consumer_key text DEFAULT NULL,
                      secret varchar(1024) NOT NULL,
                      lti_version varchar(10) DEFAULT NULL,
                      consumer_name varchar(255) DEFAULT NULL,
                      consumer_version varchar(255) DEFAULT NULL,
                      consumer_guid varchar(1024) DEFAULT NULL,
                      profile text DEFAULT NULL,
                      tool_proxy text DEFAULT NULL,
                      settings text DEFAULT NULL,
                      protected tinyint(1) NOT NULL,
                      enabled tinyint(1) NOT NULL,
                      enable_from datetime DEFAULT NULL,
                      enable_until datetime DEFAULT NULL,
                      last_access date DEFAULT NULL,
                      created datetime NOT NULL,
                      updated datetime NOT NULL,
                      PRIMARY KEY (consumer_pk)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
				);

				$this->wpdb->query(
					"ALTER TABLE `{$this->wpdb->ltitable}`
                    ADD UNIQUE INDEX {$this->wpdb->ltitable}_consumer_key_UNIQUE (consumer_key256 ASC);"
				);

				$this->wpdb->query(
					'CREATE TABLE IF NOT EXISTS `' . $this->wpdb->base_prefix . 'lti2_tool_proxy` (
                      tool_proxy_pk int(11) NOT NULL AUTO_INCREMENT,
                      tool_proxy_id varchar(32) NOT NULL,
                      consumer_pk int(11) NOT NULL,
                      tool_proxy text NOT NULL,
                      created datetime NOT NULL,
                      updated datetime NOT NULL,
                      PRIMARY KEY (tool_proxy_pk)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;'
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_tool_proxy`
                    ADD CONSTRAINT {$this->wpdb->base_prefix}lti2_tool_proxy_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
                    REFERENCES {$this->wpdb->ltitable} (consumer_pk);"
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_tool_proxy`
                    ADD INDEX {$this->wpdb->base_prefix}lti2_tool_proxy_consumer_id_IDX (consumer_pk ASC);"
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_tool_proxy`
                    ADD UNIQUE INDEX {$this->wpdb->base_prefix}lti2_tool_proxy_tool_proxy_id_UNIQUE (tool_proxy_id ASC);"
				);

				$this->wpdb->query(
					'CREATE TABLE IF NOT EXISTS `' . $this->wpdb->base_prefix . 'lti2_nonce` (
                      consumer_pk int(11) NOT NULL,
                      value varchar(32) NOT NULL,
                      expires datetime NOT NULL,
                      PRIMARY KEY (consumer_pk, value)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;'
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_nonce`
                    ADD CONSTRAINT {$this->wpdb->base_prefix}lti2_nonce_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
                    REFERENCES {$this->wpdb->base_prefix}lti2_consumer (consumer_pk);"
				);

				$this->wpdb->query(
					'CREATE TABLE IF NOT EXISTS `' . $this->wpdb->base_prefix . 'lti2_context` (
                      context_pk int(11) NOT NULL AUTO_INCREMENT,
                      consumer_pk int(11) NOT NULL,
                      lti_context_id varchar(255) NOT NULL,
                      settings text DEFAULT NULL,
                      created datetime NOT NULL,
                      updated datetime NOT NULL,
                      PRIMARY KEY (context_pk)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;'
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_context`
                    ADD CONSTRAINT {$this->wpdb->base_prefix}lti2_context_lti2_consumer_FK1 FOREIGN KEY (consumer_pk)
                    REFERENCES {$this->wpdb->base_prefix}lti2_consumer (consumer_pk);"
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_context`
                    ADD INDEX {$this->wpdb->base_prefix}lti2_context_consumer_id_IDX (consumer_pk ASC);"
				);

				$this->wpdb->query(
					'CREATE TABLE IF NOT EXISTS `' . $this->wpdb->base_prefix . 'lti2_resource_link` (
                      resource_link_pk int(11) AUTO_INCREMENT,
                      context_pk int(11) DEFAULT NULL,
                      consumer_pk int(11) DEFAULT NULL,
                      lti_resource_link_id varchar(255) NOT NULL,
                      settings text,
                      primary_resource_link_pk int(11) DEFAULT NULL,
                      share_approved tinyint(1) DEFAULT NULL,
                      created datetime NOT NULL,
                      updated datetime NOT NULL,
                      PRIMARY KEY (resource_link_pk)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;'
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_resource_link`
                    ADD CONSTRAINT {$this->wpdb->base_prefix}lti2_resource_link_lti2_context_FK1 FOREIGN KEY (context_pk)
                    REFERENCES {$this->wpdb->base_prefix}lti2_context (context_pk);"
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_resource_link`
                    ADD CONSTRAINT {$this->wpdb->base_prefix}lti2_resource_link_lti2_resource_link_FK1 FOREIGN KEY (primary_resource_link_pk)
                    REFERENCES {$this->wpdb->base_prefix}lti2_resource_link (resource_link_pk);"
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_resource_link`
                    ADD INDEX {$this->wpdb->base_prefix}lti2_resource_link_consumer_pk_IDX (consumer_pk ASC);"
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_resource_link`
                    ADD INDEX {$this->wpdb->base_prefix}lti2_resource_link_context_pk_IDX (context_pk ASC);"
				);

				$this->wpdb->query(
					'CREATE TABLE IF NOT EXISTS `' . $this->wpdb->base_prefix . 'lti2_user_result` (
                      user_pk int(11) AUTO_INCREMENT,
                      resource_link_pk int(11) NOT NULL,
                      lti_user_id varchar(255) NOT NULL,
                      lti_result_sourcedid varchar(1024) NOT NULL,
                      created datetime NOT NULL,
                      updated datetime NOT NULL,
                      PRIMARY KEY (user_pk)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;'
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_user_result`
                    ADD CONSTRAINT {$this->wpdb->base_prefix}lti2_user_result_lti2_resource_link_FK1 FOREIGN KEY (resource_link_pk)
                    REFERENCES {$this->wpdb->base_prefix}lti2_resource_link (resource_link_pk);"
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_user_result`
                    ADD INDEX {$this->wpdb->base_prefix}lti2_user_result_resource_link_pk_IDX (resource_link_pk ASC);"
				);

				$this->wpdb->query(
					'CREATE TABLE IF NOT EXISTS `' . $this->wpdb->base_prefix . 'lti2_share_key` (
                      share_key_id varchar(32) NOT NULL,
                      resource_link_pk int(11) NOT NULL,
                      auto_approve tinyint(1) NOT NULL,
                      expires datetime NOT NULL,
                      PRIMARY KEY (share_key_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;'
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_share_key`
                    ADD CONSTRAINT {$this->wpdb->base_prefix}lti2_share_key_lti2_resource_link_FK1 FOREIGN KEY (resource_link_pk)
                    REFERENCES {$this->wpdb->base_prefix}lti2_resource_link (resource_link_pk);"
				);

				$this->wpdb->query(
					'ALTER TABLE `' . $this->wpdb->base_prefix . "lti2_share_key`
                    ADD INDEX {$this->wpdb->base_prefix}lti2_share_key_resource_link_pk_IDX (resource_link_pk ASC);"
				);
			}
		}
	}

	/**
	 * Create blogs meta table if it doesn't exist
	 *
	 * @return void
	 */
	public function lti_maybe_create_site_blogs_meta_table() {
		$this->wpdb->blogsmetatable = $this->wpdb->base_prefix . 'blogs_meta';

		if ( is_user_logged_in() && is_super_admin() ) {
			if ( $this->wpdb->get_var( "SHOW TABLES LIKE '{$this->wpdb->blogsmetatable}'" ) != $this->wpdb->blogsmetatable ) {
				$this->wpdb->query(
					"CREATE TABLE IF NOT EXISTS `{$this->wpdb->blogsmetatable}` (
                      id int(11) NOT NULL AUTO_INCREMENT,
                      blog_id  bigint(20) NOT NULL,
                      version int(11) NOT NULL,
                      course_id varchar(256) NOT NULL,
                      resource_link_id varchar(256) NOT NULL,
                      blog_type varchar(256) NOT NULL,
                      creator_id bigint(20) NOT NULL,
                      creator_firstname varchar(256),
                      creator_lastname varchar(256),
                      PRIMARY KEY (id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
				);

				$this->wpdb->query(
					"ALTER TABLE `{$this->wpdb->blogsmetatable}`
                    ADD CONSTRAINT `{$this->wpdb->blogsmetatable}_site_FK1` FOREIGN KEY (blog_id)
                    REFERENCES {$this->wpdb->base_prefix}blogs (blog_id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE;"
				);
			}
		}
	}
}
