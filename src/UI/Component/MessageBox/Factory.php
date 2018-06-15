<?php

/* Copyright (c) 2018 Thomas Famula <famula@leifos.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Component\MessageBox;

/**
 * This is how a factory for Message Boxes looks like.
 */
interface Factory {
	/**
	 * ---
	 * description:
	 *   purpose: >
	 *     The system failed to complete some actions and displays information about the failure (brand-danger).
	 *
	 * rules:
	 *   interaction:
	 *       1: >
	 *          Failure Message Boxes MAY be interactive.
	 * ---
	 *
	 * @return \ILIAS\UI\Component\MessageBox\MessageBox
	 */
	public function failure($message_text);

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *     The system succeeded in finishing some action and displays a success message (brand-success).
	 *
	 * rules:
	 *   interaction:
	 *       1: >
	 *          Success Message Boxes MAY be interactive.
	 * ---
	 *
	 * @return \ILIAS\UI\Component\MessageBox\MessageBox
	 */
	public function success($message_text);

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *     The system informs the user about obstacles standing in the way of completing a workflow
	 *     or about side-effects of his or her actions on other users.
	 *
	 * rules:
	 *   interaction:
	 *       1: >
	 *          Info Message Boxes MAY contain shortcuts or actions displayed as Buttons. Buttons being used as shortcuts
	 *          SHOULD be exceptions, e.g. if a Button inside the Info Message Box takes the user directly to the location where
	 *          the issue can be solved by the user (i.e. Participants-Tab of Survey to delete participant data before editing questions).
	 * ---
	 *
	 * @return \ILIAS\UI\Component\MessageBox\MessageBox
	 */
	public function info($message_text);

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *     The system needs input from the user.
	 *
	 * rules:
	 *   interaction:
	 *       1: >
	 *          Confirmation Message Boxes MUST be interactive.
	 * ---
	 *
	 * @return \ILIAS\UI\Component\MessageBox\MessageBox
	 */
	public function confirmation($message_text);

}