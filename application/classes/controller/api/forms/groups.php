<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Ushahidi API Forms Groups Controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @subpackage Controllers
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License Version 3 (GPLv3)
 */

class Controller_API_Forms_Groups extends Ushahidi_API {

	/**
	 * Create a new group
	 * 
	 * POST /api/forms/:form_id/groups
	 * 
	 * @return void
	 */
	public function action_post_index_collection()
	{
		$form_id = $this->request->param('form_id');
		$results = array();
		$post = $this->_request_payload;
		
		$form = ORM::factory('form', $form_id);
		
		if ( ! $form->loaded())
		{
			// @todo throw 400 or 404
			$this->_response_payload = array(
				'errors' => array(
					'Form does not exist'
					)
				);
			return;
		}
		
		$group = ORM::factory('form_group')->values($post);
		$group->form_id = $form_id;
		
		// Validation - perform in-model validation before saving
		try
		{
			// Validate base group data
			$group->check();

			// Validates ... so save
			$group->values($post, array(
				'label', 'priority'
				));
			$group->save();

			// Response is the complete form
			$this->_response_payload = $this->group($group);
		}
		catch (ORM_Validation_Exception $e)
		{
			// @todo throw 400
			// Error response
			$this->_response_payload = array(
				'errors' => Arr::flatten($e->errors('models'))
				);
		}
	}

	/**
	 * Retrieve all groups
	 * 
	 * GET /api/forms/:form_id/groups
	 * 
	 * @return void
	 */
	public function action_get_index_collection()
	{
		$form_id = $this->request->param('form_id');
		$results = array();

		$groups = ORM::factory('form_group')
			->order_by('id', 'ASC')
			->where('form_id', '=', $form_id)
			->find_all();

		$count = $groups->count();

		foreach ($groups as $group)
		{
			$results[] = $this->group($group);
		}

		// Respond with groups
		$this->_response_payload = array(
			'count' => $count,
			'results' => $results
			);
	}

	/**
	 * Retrieve a group
	 * 
	 * GET /api/forms/:form_id/groups/:id
	 * 
	 * @return void
	 */
	public function action_get_index()
	{
		$form_id = $this->request->param('form_id');
		$id = $this->request->param('id');
		$results = array();

		$group = ORM::factory('form_group')
			->where('form_id', '=', $form_id)
			->where('id', '=', $id)
			->find();

		// Respond with group
		$this->_response_payload = $this->group($group);
	}

	/**
	 * Update a single group
	 * 
	 * PUT /api/forms/:form_id/groups/:id
	 * 
	 * @return void
	 */
	public function action_put_index()
	{
		$form_id = $this->request->param('form_id');
		$id = $this->request->param('id');
		$results = array();
		$post = $this->_request_payload;

		$group = ORM::factory('form_group')
			->where('form_id', '=', $form_id)
			->where('id', '=', $id)
			->find();
		
		if ( ! $group->loaded())
		{
			// @todo throw 404
			$this->_response_payload = array(
				'errors' => array(
					'Group does not exist'
					)
				);
			return;
		}
		
		// Load post values into group model
		$group->values($post);
		
		$group->id = $id;
		
		// Validation - perform in-model validation before saving
		try
		{
			// Validate base group data
			$group->check();

			// Validates ... so save
			$group->values($post, array(
				'label', 'priority'
				));
			$group->save();

			// Response is the complete form
			$this->_response_payload = $this->group($group);
		}
		catch (ORM_Validation_Exception $e)
		{
			// Error response
			$this->_response_payload = array(
				'errors' => Arr::flatten($e->errors('models'))
				);
		}
	}

	/**
	 * Delete a single group
	 * 
	 * DELETE /api/forms/:form_id/groups/:id
	 * 
	 * @return void
	 */
	public function action_delete_index()
	{
		$id = $this->request->param('id');
		$form_id = $this->request->param('form_id');

		$group = ORM::factory('form_group')
			->where('form_id', '=', $form_id)
			->where('id', '=', $id)
			->find();

		$this->_response_payload = array();
		if ( $group->loaded() )
		{
			// Return the group we just deleted (provides some confirmation)
			$this->_response_payload = $this->group($group);
			$group->delete();
		}
	}
	
	/**
	 * Retrieve group's attributes
	 * 
	 * GET /api/forms/:form_id/groups/:id/attributes
	 * 
	 * @return void
	 */
	public function action_get_attributes()
	{
		$form_id = $this->request->param('form_id');
		$id = $this->request->param('id');
		$results = array();

		$attributes = ORM::factory('form_attribute')
			->order_by('id', 'ASC')
			->where('form_id', '=', $form_id)
			->where('form_group_id', '=', $id)
			->find_all();
		
		$count = $attributes->count();

		foreach ($attributes as $attribute)
		{
			$results[] = Controller_API_Forms_Attributes::attribute($attribute);
		}

		// Respond with attributes
		$this->_response_payload = array(
			'count' => $count,
			'results' => $results
			);
	}
	
	/**
	 * Add new attribute to group
	 * 
	 * POST /api/forms/:form_id/groups/:id/attributes
	 * 
	 * @todo share code between this and POST /api/forms/:form_id/attributes
	 * @return void
	 */
	public function action_post_attributes()
	{
		$form_id = $this->request->param('form_id');
		$group_id = $this->request->param('id');
		$results = array();
		$post = $this->_request_payload;
		
		$form = ORM::factory('form', $form_id);
		
		if ( ! $form->loaded())
		{
			// @todo throw 400 or 404
			$this->_response_payload = array(
				'errors' => array(
					'Form does not exist'
					)
				);
			return;
		}
		
		$group = ORM::factory('form_group', $group_id);
		
		if ( ! $group->loaded())
		{
			// @todo throw 400 or 404
			$this->_response_payload = array(
				'errors' => array(
					'Group does not exist'
					)
				);
			return;
		}
		
		$attribute = ORM::factory('form_attribute')->values($post);
		$attribute->form_id = $form_id;
		$attribute->form_group_id = $group_id;
		
		// Validation - perform in-model validation before saving
		try
		{
			// Validate base group data
			$attribute->check();

			// Validates ... so save
			$attribute->values($post, array(
				'key', 'label', 'input', 'type'
				));
			$attribute->save();

			// Response is the complete form
			$this->_response_payload = Controller_API_Forms_Attributes::attribute($attribute);
		}
		catch (ORM_Validation_Exception $e)
		{
			// @todo throw 400
			// Error response
			$this->_response_payload = array(
				'errors' => Arr::flatten($e->errors('models'))
				);
		}
	}

	/**
	 * Retrieve a single group, along with all its attributes
	 * 
	 * @param $group object - group model
	 * @return array $response
	 */
	public static function group($group = NULL)
	{
		$response = array();
		if ( $group->loaded() )
		{
			$response = array(
				'url' => url::site('api/v2/forms/'.$group->form_id.'/groups/'.$group->id, Request::current()),
				'form' => url::site('api/v2/forms/'.$group->form_id, Request::current()),
				'id' => $group->id,
				'label' => $group->label,
				'priority' => $group->priority,
				'attributes' => array()
				);
			
			foreach ($group->form_attributes->find_all() as $attribute)
			{
				$response['attributes'][] = Controller_API_Forms_Attributes::attribute($attribute);
			}
		}
		else
		{
			// @todo throw 404
			$response = array(
				'errors' => array(
					'Group does not exist'
					)
				);
		}

		return $response;
	}
}