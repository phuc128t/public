/**
     * Eric: mask credit card in XML string
     * @param $xml
     * @return string
     */
    public function maskCCinXml($xml) {
        try {
            $elToReplace = 'cardNumber';
            $dom = new DOMDocument();
            $dom->loadXML($xml);

            if($dom->getElementsByTagName($elToReplace)->length) {
                $cc = $this->maskCC($dom->getElementsByTagName("cardNumber")->item(0)->nodeValue);
                $dom->getElementsByTagName($elToReplace)->item(0)->nodeValue = '';
                $dom->getElementsByTagName($elToReplace)->item(0)->appendChild($dom->createTextNode($cc));
            }
            return $dom->saveXML();
        }
        catch (Exception $e) {}

        return $xml;
    }

    public function maskCC($cc) {
        $cc_length = strlen($cc);
        for ($i = 0; $i < $cc_length - 4; $i++) {
            if ($cc[$i] == '-') {
                continue;
            }
            $cc[$i] = 'X';
        }

        return $cc;
    }
