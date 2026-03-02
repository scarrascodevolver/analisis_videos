<?php

namespace App\Http\Controllers;

use App\Services\LongoMatchXmlParser;
use Illuminate\Http\Request;

class DirectUploadController extends Controller
{
    protected LongoMatchXmlParser $xmlParser;

    public function __construct(LongoMatchXmlParser $xmlParser)
    {
        $this->xmlParser = $xmlParser;
    }

    /**
     * Validate LongoMatch XML content
     */
    public function validateXml(Request $request)
    {
        $request->validate([
            'xml_content' => 'required|string',
        ]);

        $result = $this->xmlParser->validate($request->xml_content);

        return response()->json($result);
    }
}
