<?php

class Test_Entry_Query extends WP_UnitTestCase {
	const JAN_1_TIMESTAMP = 1325376000;
	const JAN_1_MYSQL = '2012-01-01 00:00:00';

	const JAN_2_TIMESTAMP = 1325462400;
	const JAN_2_MYSQL = '2012-01-02 00:00:00';

	function setUp() {
		parent::setUp();
		wp_delete_comment( 1, true );
		$this->entry_query = new WPCOM_Liveblog_Entry_Query( 5, 'baba' );
	}

	function test_get_latest_should_return_null_if_no_comments() {
		$this->assertNull( $this->entry_query->get_latest() );
	}

	function test_get_latest_should_return_the_only_comment_if_one() {
		$id = $this->create_comment();
		$latest_entry = $this->entry_query->get_latest();
		$this->assertEquals( $id, $latest_entry->get_id() );
	}

	function test_get_latest_should_return_the_latest_comment_if_more_than_one() {
		$id_first = $this->create_comment( array( 'comment_date_gmt' => self::JAN_1_MYSQL ) );
		$id_second = $this->create_comment( array( 'comment_date_gmt' => self::JAN_2_MYSQL ) );
		$latest_entry = $this->entry_query->get_latest();
		$this->assertEquals( $id_second, $latest_entry->get_id() );
	}

	function test_get_latest_timestamp_should_properly_convert_to_unix_timestamp() {
		$this->create_comment( array( 'comment_date_gmt' => self::JAN_1_MYSQL) );
		$this->assertEquals( self::JAN_1_TIMESTAMP, $this->entry_query->get_latest_timestamp() );
	}

	function test_get_between_timestamps_should_return_an_entry_between_two_timestamps() {
		$id_first = $this->create_comment( array( 'comment_date_gmt' => self::JAN_1_MYSQL ) );
		$id_second = $this->create_comment( array( 'comment_date_gmt' => self::JAN_2_MYSQL ) );
		$entries = $this->entry_query->get_between_timestamps( self::JAN_1_TIMESTAMP - 10, self::JAN_2_TIMESTAMP + 10 );
		$this->assertEquals( 2, count( $entries )  );
		$ids = $this->get_ids_from_entries( $entries );
		$this->assertContains( $id_first, $ids );
		$this->assertContains( $id_second, $ids );
	}

	function test_get_between_timestamps_should_return_entries_on_the_border() {
		$id= $this->create_comment( array( 'comment_date_gmt' => self::JAN_1_MYSQL ) );
		$entries = $this->entry_query->get_between_timestamps( self::JAN_1_TIMESTAMP, self::JAN_1_TIMESTAMP + 1 );
		$ids = $this->get_ids_from_entries( $entries );
		$this->assertEquals( array( $id ), $ids );
	}

	function test_get_only_matches_comments_with_the_key_as_approved_status() {
		$id = $this->create_comment( array( 'comment_approved' => 'wink' ) );
		$entries = $this->entry_query->get();
		$this->assertEquals( 0, count( $entries ) );
	}

	private function create_comment( $args = array() ) {
		static $number = 0;
		$number++;
		$defaults = array(
			'comment_post_ID' => $this->entry_query->post_id,
			'comment_content' => 'Comment Text ' . $number,
			'comment_approved' => $this->entry_query->key,
			'comment_type' => $this->entry_query->key,
			'user_id' => 1,
			'comment_author' => 'Baba',
			'comment_author_email' => 'baba@baba.net',
		);
		$args = array_merge( $defaults, $args );
		// TODO: addslashes deep
		return wp_insert_comment( $args );
	}

	private function get_ids_from_entries( $entries ) {
		return array_map( function( $entry ) { return $entry->get_id(); }, $entries );
	}

}