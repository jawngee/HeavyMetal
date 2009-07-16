<?php
class ReqMethodController extends Controller
{
	public function GET_index()
	{
		return array(
			'method' => 'GET',
			'string' => $this->request->input->string,
			'number' => $this->request->input->number
		);
	}

	public function PUT_index()
	{
		return array(
			'method' => 'PUT',
			'string' => $this->request->input->string,
			'number' => $this->request->input->number
		);
	}

	public function POST_index()
	{
		return array(
			'method' => 'POST',
			'string' => $this->request->input->string,
			'number' => $this->request->input->number
		);
	}

	public function DELETE_index()
	{
		return array(
			'method' => 'DELETE',
			'string' => $this->request->input->string,
			'number' => $this->request->input->number
		);
	}

	public function HELLOKITTY_index()
	{
		return array(
			'method' => 'HELLOKITTY',
			'string' => $this->request->input->string,
			'number' => $this->request->input->number
		);
	}
}