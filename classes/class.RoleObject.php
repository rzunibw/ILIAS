<?php
/**
* Class RoleObject
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
* 
* @extends Object
* @package ilias-core
*/
class RoleObject extends Object
{
	/**
	* Constructor
	* @access	public
	*/
	function RoleObject()
	{
		$this->Object();
	}
	
	//
	// Overwritten methods:
	//
	
	/**
	* create a role object 
	* @access	public
	*/
	function createObject()
	{
		// Creates a child object
		global $tplContent, $rbacsystem;

		if ($rbacsystem->checkAccess("write",$_GET["obj_id"],$_GET["parent"]))
		{
			$tplContent = new Template("object_form.html",true,true);
			$tplContent->setVariable($this->ilias->ini["layout"]);

			// Zur Ausgabe des 'Path' wird die Private-Methode getPath() aufgerufen 
			$tplContent->setVariable("TREEPATH",$this->getPath());
			$tplContent->setVariable("CMD","save");
			$tplContent->setVariable("OBJ_ID",$_GET["obj_id"]);
			$tplContent->setVariable("TPOS",$_GET["parent"]);
			$tplContent->setVariable("TYPE",$_POST["type"]);
		}
		else
		{
			$this->ilias->raiseError("No permission to write to role folder",$this->ilias->error_obj->WARNING);
		}
	}

	/**
	* save a new role object
	* @access	public
	**/
	function saveObject()
	{
		global $rbacsystem, $rbacadmin;

		// CHECK ACCESS 'write' to role folder
		if ($rbacsystem->checkAccess('write',$_GET["obj_id"],$_GET["parent"]))
		{
			if ($rbacadmin->roleExists($_POST["Fobject"]["title"]))
			{
				$this->ilias->raiseError("Role Exists",$this->ilias->error_obj->WARNING);
			}

			$new_obj_id = createNewObject($_POST["type"],$_POST["Fobject"]);
			$rbacadmin->assignRoleToFolder($new_obj_id,$_GET["obj_id"],$_GET["parent"],'y');
		}
		else
		{
			$this->ilias->raiseError("No permission to write to role folder",$this->ilias->error_obj->WARNING);
		}
		
		header("Location: content.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]);
		exit;
	}

	/**
	* delete a role object
	* @access	public
	**/
	function deleteObject()
	{
		global $tree, $rbacsystem, $rbacadmin;
		
		// Erst muss das Recht zum L�schen im RoleFolder �berpr�ft werden
		// Auslesen aller RoleFolderId's aus rbac_fa
		// => alle Id's sind Kinder oder es gibt keine anderen RoleFolder
		//    deleteRole()
		// => sonst deleteLocalRole() f�r alle Kinder und den zu l�schenden RoleFolder
		if ($rbacsystem->checkAccess('write',$_GET["obj_id"],$_GET["parent"]))
		{
			if ($_POST["id"])
			{
				// if object is system role folder these vars are used by method isGrandChild()
				$parent = $_GET["parent"] == SYSTEM_FOLDER_ID ? 0 : $_GET["parent"];
				$object_id = $_GET["parent"] == SYSTEM_FOLDER_ID ? ROOT_FOLDER_ID : $_GET["obj_id"];
				
				foreach ($_POST["id"] as $id)
				{
					$folders = $rbacadmin->getFoldersAssignedToRole($id);
					
					if (count($folders) == 1)
					{
						$rbacadmin->deleteRole($id);
					}
					else
					{
						foreach ($folders as $folder)
						{
							if($tree->isGrandChild($object_id,$parent,$folder["parent" ],$folder["parent_obj"]))
							{
								$to_delete[] = array(
									"parent"     => $folder["parent"],
									"parent_obj" => $folder["parent_obj"]);
							}
						}
						// are all childs?
						if (count($to_delete) == count($folders))
						{
							$rbacadmin->deleteRole($id);
						}
						else
						{
							foreach ($to_delete as $delete)
							{
								$rbacadmin->deleteLocalRole($id,$delete["parent"],$delete["parent_obj"]);
							}
						}
					}
				}
			}
			else
			{
				$this->ilias->raiseError("No check box checked, nothing happened ;-).",$this->ilias->error_obj->MESSAGE);
			}
		}
		else
		{
			$this->ilias->raiseError("No permission to write to role folder",$this->ilias->error_obj->MESSAGE);
		}

		header("Location: content_role.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]);
		exit;
	}

	/**
	* edit a role object
	* @access	public
	* 
	**/
	function editObject()
	{
		global $tplContent, $rbacsystem;

		if ($rbacsystem->checkAccess('write',$_GET["parent"],$_GET["parent_parent"]))
		{
			$tplContent = new Template("object_form.html",true,true);
			$tplContent->setVariable($this->ilias->ini["layout"]);
			$tplContent->setVariable("OBJ_SELF","content.php?obj_id=".$_GET["parent"]."&parent=".$_GET["parent_parent"]);
			$tplContent->setVariable("TARGET","object.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"].
									 "&parent_parent=".$_GET["parent_parent"]);
			$tplContent->setVariable("TREEPATH",$this->getPath($_GET["parent"],$_GET["parent_parent"]));
			$tplContent->setVariable("CMD","update");
			$tplContent->setVariable("TPOS",$_GET["parent"]);

			$obj = getObject($_GET["obj_id"]);
			$tplContent->setVariable("TYPE",$obj["type"]);

			$tplContent->setVariable("OBJ_ID",$obj["obj_id"]);
			$tplContent->setVariable("OBJ_TITLE",$obj["title"]);
			$tplContent->setVariable("OBJ_DESC",$obj["desc"]);
		}
		else
		{
			$this->ilias->raiseError("No permission to edit the object",$this->ilias->error_obj->WARNING);
		}			
	}

	/**
	* update a role object
	* @access	public
	**/
	function updateObject()
	{
		global $rbacsystem;

		if ($rbacsystem->checkAccess('write',$_GET["parent"],$_GET["parent_parent"]))
		{
			updateObject($_GET["obj_id"],$_GET["type"],$_POST["Fobject"]);
			header("Location: content.php?obj_id=".$_GET["parent"]."&parent=".$_GET["parent_parent"]);
			exit;
		}
		else
		{
			$this->ilias->raiseError("No permission to edit the object",$this->ilias->error_obj->WARNING);
		}
	}

	/**
	* show permission templates of role object
	* @access	public
	**/
	function permObject() 
	{
		global $tree, $tplContent, $rbacadmin, $rbacreview, $rbacsystem;

		if ($rbacsystem->checkAccess('write',$_GET["parent"],$_GET["parent_parent"]))
		{
			$tplContent = new Template("role_perm.html",true,true);
			$tplContent->setVariable("TPOS",$_GET["parent"]);
			$tplContent->setVariable("OBJ_ID",$_GET["obj_id"]);
			$tplContent->setVariable("PAR",$_GET["parent_parent"]);
			$tplContent->setVariable($this->ilias->ini["layout"]);
			$tplContent->setVariable("TREEPATH",$this->getPath($_GET["parent"],$_GET["parent_parent"]));

			$role_data = $rbacadmin->getRoleData($_GET["obj_id"]);
			$tplContent->setVariable("MESSAGE_TOP","Permission Template of Role: ".$role_data["title"]);

			$obj_data = getTypeList();
			// BEGIN OBJECT_TYPES
			$tplContent->setCurrentBlock("OBJECT_TYPES");

			foreach ($obj_data as $data)
			{
				$tplContent->setVariable("OBJ_TYPES",$data["title"]);
				$tplContent->parseCurrentBlock();
			}

			// END OBJECT TYPES
			$all_ops = getOperationList();
			// BEGIN TABLE_DATA_OUTER

			foreach ($all_ops as $key => $operations)
			{
				// BEGIN CHECK_PERM
				$tplContent->setCurrentBlock("CHECK_PERM");

				foreach ($obj_data as $data)
				{
					if (in_array($operations["ops_id"],$rbacadmin->getOperationsOnType($data["obj_id"])))
					{
						$selected = $rbacadmin->getRolePermission($_GET["obj_id"],$data["title"],$_GET["parent"]);

						$checked = in_array($operations["ops_id"],$selected);
						// Es wird eine 2-dim Post Variable �bergeben: perm[rol_id][ops_id]
						$box = TUtil::formCheckBox($checked,"template_perm[".$data["title"]."][]",$operations["ops_id"]);
						$tplContent->setVariable("CHECK_PERMISSION",$box);
					}
					else
					{
						$tplContent->setVariable("CHECK_PERMISSION","");
					}

					$tplContent->parseCurrentBlock();
				}

				// END CHECK_PERM
				$tplContent->setCurrentBlock("TABLE_DATA_OUTER");
				// color changing
				$css_row = TUtil::switchColor($key,"row_high","row_low");
				$tplContent->setVariable("CSS_ROW",$css_row);
				$tplContent->setVariable("PERMISSION",$operations["operation"]);
				$tplContent->parseCurrentBlock();
			}

			$box = TUtil::formCheckBox($checked,"recursive",1);
			$tplContent->setVariable("COL_ANZ",count($obj_data));
			$tplContent->setVariable("CHECK_BOTTOM",$box);
			$tplContent->setVariable("MESSAGE_TABLE","Change existing objects");		

		
			// USER ASSIGNMENT
			if ($rbacadmin->isAssignable($_GET["obj_id"],$_GET["parent"]))
			{
				$tplContent->setCurrentBlock("ASSIGN");
				$users = getUserList();
				$assigned_users = $rbacreview->assignedUsers($_GET["obj_id"]);

				$tplContent->setVariable("MESSAGE_BOTTOM","Assign User To Role");
				$tplContent->setCurrentBLock("TABLE_USER");
				$tplContent->setVariable("ASSIGN_PAR",$_GET["parent_parent"]);

				foreach ($users as $key => $user)
				{
					$tplContent->setVariable("CSS_ROW_USER",$key % 2 ? "row_low" : "row_high");
					$checked = in_array($user["obj_id"],$assigned_users);
					$box = TUtil::formCheckBox($checked,"user[]",$user["obj_id"]);
					$tplContent->setVariable("CHECK_USER",$box);
					$tplContent->setVariable("USERNAME",$user["title"]);
					$tplContent->parseCurrentBlock();
				}

				$tplContent->setVariable("ASSIGN_OBJ_ID",$_GET["obj_id"]);
				$tplContent->setVariable("ASSIGN_TPOS",$_GET["parent"]);
				$tplContent->parseCurrentBlock();
			}
			// ADOPT PERMISSIONS
			$tplContent->setVariable("MESSAGE_MIDDLE","Adopt Permissions from Role Template");
			
			// BEGIN ADOPT_PERMISSIONS
			$tplContent->setCurrentBlock("ADOPT_PERMISSIONS");
			$parent_role_ids = $rbacadmin->getParentRoleIds($_GET["parent"],$_GET["parent_parent"],true);

			// sort output for correct color changing
			ksort($parent_role_ids);

			foreach ($parent_role_ids as $key => $par)
			{
				$radio = TUtil::formRadioButton(0,"adopt",$par["obj_id"]);
				$tplContent->setVariable("CSS_ROW_ADOPT",TUtil::switchColor($key,"row_high","row_low"));
				$tplContent->setVariable("CHECK_ADOPT",$radio);
				$tplContent->setVariable("TYPE",$par["type"] == 'role' ? 'Role' : 'Template');
				$tplContent->setVariable("ROLE_NAME",$par["title"]);
				$tplContent->parseCurrentBlock();
			}
			// END ADOPT_PERMISSIONS
		}
		else
		{
			$this->ilias->raiseError("No permission to write to role folder",$this->ilias->error_obj->WARNING);
		}
	}

	/**
	* save permission templates of a role object
	* @access	public
	**/
	function permSaveObject()
	{
		global $tree, $rbacsystem, $rbacadmin;

		if ($rbacsystem->checkAccess('edit permission',$_GET["parent"],$_GET["parent_parent"]))
		{
			// delete all template entries
			$rbacadmin->deleteRolePermission($_GET["obj_id"],$_GET["parent"]);

			foreach ($_POST["template_perm"] as $key => $ops_array)
			{
				// sets new template permissions
				$rbacadmin->setRolePermission($_GET["obj_id"],$key,$ops_array,$_GET["parent"]);
			}
			// Existierende Objekte anpassen
			if ($_POST["recursive"])
			{
				$parent_obj = $_GET["parent_parent"];
				if ($parent_obj == SYSTEM_FOLDER_ID)
				{
					$object_id = ROOT_FOLDER_ID;
					$parent = 0;
				}
				else
				{
					$object_id = $_GET["parent"];
					$parent = $_GET["parent_parent"];
				}
				// revoke all permissions where no permissions are set 
				$types = getTypeList();

				foreach ($types as $type)
				{
					$typ = $type["title"];

					if (!is_array($_POST["template_perm"][$typ]))
					{
						$objects = $tree->getAllChildsByType($object_id,$parent,$typ);

						foreach ($objects as $object)
						{
							$rbacadmin->revokePermission($object["obj_id"],$_GET["obj_id"],$object["parent"]);
						}
					}
				}

				foreach ($_POST["template_perm"] as $key => $ops_array)
				{
					$objects = $tree->getAllChildsByType($object_id,$parent,$key);

					foreach ($objects as $object)
					{
						$rbacadmin->revokePermission($object["obj_id"],$_GET["obj_id"],$object["parent"]);
						$rbacadmin->grantPermission($_GET["obj_id"],$ops_array,$object["obj_id"],$object["parent"]);
					}
				}
			}
		}
		else
		{
			$this->ilias->raiseError("No permission to edit permissions",$this->ilias->error_obj->WARNING);
		}
		
		header("location:object.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&parent_parent=".$_GET["parent_parent"]."&cmd=perm");
		exit;
	}

	/**
	* copy permissions from role
	* @access	public
	**/
	function adoptPermSaveObject()
	{
		global $rbacadmin, $rbacsystem;

		if ($rbacsystem->checkAccess('edit permission',$_GET["parent"],$_GET["parent_parent"]))
		{
			$rbacadmin->deleteRolePermission($_GET["obj_id"],$_GET["parent"]);
			$parentRoles = $rbacadmin->getParentRoleIds($_GET["parent"],$_GET["parent_parent"],true);
			$rbacadmin->copyRolePermission($_POST["adopt"],$parentRoles["$_POST[adopt]"]["parent"],$_GET["parent"],$_GET["obj_id"]);
		}
		else
		{
			$this->ilias->raiseError("No Permission to edit permissions",$this->ilias->error_obj->WARNING);
		}

		header("location:object.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&parent_parent=".$_GET["parent_parent"]."&cmd=perm");
		exit;
	}

	/**
	* assign user to role
	* @access	public
	**/
	function assignSaveObject()
	{
		global $tree, $rbacsystem, $rbacadmin, $rbacreview;
		 
		if ($rbacadmin->isAssignable($_GET["obj_id"],$_GET["parent"]))
		{
			if ($rbacsystem->checkAccess('edit permission',$_GET["parent"],$_GET["parent_parent"]))
			{
				$assigned_users = $rbacreview->assignedUsers($_GET["obj_id"]);
				$_POST["user"] = $_POST["user"] ? $_POST["user"] : array();

				foreach (array_diff($assigned_users,$_POST["user"]) as $user)
				{
					$rbacadmin->deassignUser($_GET["obj_id"],$user);
				}

				foreach (array_diff($_POST["user"],$assigned_users) as $user)
				{
					$rbacadmin->assignUser($_GET["obj_id"],$user);
				}
			}
			else
			{
				$this->ilias->raiseError("No permission to edit permissions",$this->ilias->error_obj->WARNING);
			}

			header("location:object.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&parent_parent=".$_GET["parent_parent"]."&cmd=perm");
			exit;
		}
		else
		{
			$this->ilias->raiseError("It's worth a try. ;-)",$this->ilias->error_obj->WARNING);
		}
	}
} // END class.RoleObject
?>