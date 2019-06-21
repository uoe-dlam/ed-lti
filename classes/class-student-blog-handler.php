<?php
namespace EdLTI\classes;

/**
 * Handles student blog types.
 *
 * @author    DLAM Applications Development Team <ltw-apps-dev@ed.ac.uk>
 * @copyright University of Edinburgh
 * @license   https://www.gnu.org/licenses/gpl.html
 *
 * @link https://github.com/uoe-dlam/ed-lti
 */
class Student_Blog_Handler extends Blog_Handler {

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
	}

	/**
	 * Return the type of blog this handler creates
	 *
	 * @return string
	 */
	public function get_blog_type() {
		return 'student';
	}

	/**
	 * TODO: Not sure what this does... what is the path used for?
	 *
	 * @return string
	 */
	protected function get_path() {
		return $this->get_friendly_path( $this->username . '_' . $this->course_title );
	}

	/**
	 * Return the course title
	 *
	 * @string
	 */
	protected function get_title() {
		return $this->user->first_name . ' ' . $this->user->last_name . ' / ' . $this->course_title;
	}

	/**
	 * Create or return the existing blog.
	 * Note that we are overidding the parent method for this class.
	 *
	 * @param bool $make_private
	 *
	 * @return int
	 */
	public function first_or_create_blog( $make_private = false ) {
		if (
			null === $this->course_id || null === $this->course_title || null === $this->domain ||
			null === $this->resource_link_id || null === $this->username || null === $this->source_id ||
			null === $this->site_category
		) {
			wp_die( 'Blog_Handler: You must set all data before calling first_or_create_blog' );
		}

		$blog_id = $this->get_blog_id_if_exists();

		if ( null !== $blog_id ) {
			return $blog_id;
		}

		// If the blog path already exists, this user must already have created a student blog already. However, their id must have changed. The most likely reason is that they were deleted from the system and then added again.
		$blog_id = $this->get_blog_id_for_path();

		if ( ! empty( $blog_id ) ) {
			$this->update_blog_meta_with_user_id( $blog_id );
			return $this->get_blog_id();
		}

		return $this->create_blog( $make_private );
	}

	/**
	 * Get the maximum version of a blog type
	 *
	 * @return int
	 */
	public function get_blog_max_version() {
		$query = 'SELECT IFNULL(MAX(version), 0) AS max_version '
			. "FROM {$this->wpdb->base_prefix}blogs_meta "
			. 'WHERE course_id = %s '
			. 'AND blog_type = %s '
			. 'AND creator_id = %d';

        // phpcs:disable
		$blog_max_version = $this->wpdb->get_results(
			$this->wpdb->prepare(
				$query,
				$this->course_id,
				$this->get_blog_type(),
				$this->user->ID
			)
		);
        // phpcs:enable

		return (int) $blog_max_version[0]->max_version;
	}

	/**
	 * Return the blog ID if a blog exists.
	 *
	 * @return string
	 */
	protected function get_blog_id_if_exists() {
		$query = "SELECT {$this->wpdb->base_prefix}blogs_meta.blog_id AS blog_id "
			. "FROM {$this->wpdb->base_prefix}blogs_meta "
			. "INNER JOIN {$this->wpdb->base_prefix}blogs "
			. "ON {$this->wpdb->base_prefix}blogs.blog_id = {$this->wpdb->base_prefix}blogs_meta.blog_id "
			. 'WHERE course_id = %s '
			. 'AND resource_link_id = %s '
			. 'AND blog_type = %s '
			. 'AND creator_id = %d';

        // phpcs:disable
		$blogs = $this->wpdb->get_results(
			$this->wpdb->prepare(
				$query,
				$this->course_id,
				$this->resource_link_id,
				$this->get_blog_type(),
				$this->user->ID
			)
		);
        // phpcs:enable

		if ( ! $blogs ) {
			return null;
		}

		return $blogs[0]->blog_id;
	}

	/**
	 * Get blog id for a given path. If no match 0 is returned.
	 *
	 * @return int
	 */
	protected function get_blog_id_for_path() {
		$path    = $this->get_path();
		$version = $this->get_blog_max_version();
		$version++;

		if ( $version > 1 ) {
			// we already have a main blog, so create new blog and increment version number.
			$path .= '_v' . $version;
		}

		$path = '/' . $path . '/';

		return get_blog_id_from_url( $this->domain, $path );
	}

	/**
	 * Update blog meta with new user id. This usually happens when a user has been deleted and then re-added to the system.
	 *
	 * @param int $blog_id
	 *
	 * @return void
	 */
	protected function update_blog_meta_with_user_id( $blog_id ) {
		$this->wpdb->update(
			$this->wpdb->base_prefix . 'blogs_meta',
			[ 'creator_id' => $this->user->ID ],
			[ 'blog_id' => $blog_id ]
		);
	}

	/**
	 * Get the WordPress role for a given LTI user role
	 *
	 * @param User_LTI_Roles $user_roles
	 *
	 * @return string
	 */
	public function get_wordpress_role( User_LTI_Roles $user_roles ) {
		if ( $user_roles->is_learner() || $user_roles->is_admin() ) {
			return 'administrator';
		}

		return 'author';
	}
}
