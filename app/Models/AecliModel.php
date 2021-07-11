<?php 
namespace App\Models;

use App\Models\ComModel;

class AecliModel extends ComModel
{//AE cli 调用 Model

	public function __construct() {
		parent::__construct();
    }

	public function spendAE($address, $amount)
	{	
		$bsConfig = (new ConfigModel())-> backendConfig();
		$nodeUrl  = $bsConfig['backendServiceNode'];
		$wallet   = $bsConfig['walletPath'];
		$password = $bsConfig['walletPassword'];
		$payload  = $bsConfig['airdropPayload'];
		$aecli    = "tolink/aecli account spend -u {$nodeUrl} {$wallet} --password {$password} {$address} {$amount} --payload '{$payload}' --json";
		exec($aecli, $arr);
		$json = json_decode($arr[0], true);
		$hash = $json['tx']['hash'];
		$textFile   = fopen("airdrop/AE/".date("Y-m-d").".txt", "a");
		$appendText = $address.":".$amount.":".$hash."\r\n";
		fwrite($textFile, $appendText);
		fclose($textFile);
		return $hash;

		/*
		Array(
			[0] => 
				{"tx":
					{
						"blockHash":"mh_29mT88smzkJJQ3PJRYgh3s75yB258uMTZG3LBZntj5cdn6E52y",
						"blockHeight":444849,
						"hash":"th_o48L4ARDQUZF3JtLE2wmWUXUXPpQz9cbg4p1pkUJZA7WxdadM",
						"signatures":["sg_ibK94fKjLChbjZ3JLhf6Jse8bTKuzYmUFuj1yWRhDHQZyMPxWF17P1QoDm6AxXiAJ3FRsBhDb4LozETQUujjaGCnSLDG"],
						"tx":
							{
								"amount":100000000000000,
								"fee":17060000000000,
								"nonce":1045,
								"payload":"ba_dGVzdCBmb3IgY2xpSJUL1g==",
								"recipientId":"ak_21t5CKNRkKai3fCRm9o3WqvLKgbNKbin5k89FzkxfUEWu6G6GM",
								"senderId":"ak_21t5CKNRkKai3fCRm9o3WqvLKgbNKbin5k89FzkxfUEWu6G6GM",
								"type":"SpendTx",
								"version":1
							},
						"rawTx":"tx_+K8LAfhCuEAFey+uMBGwftkUdomrKqUoYxdo06vVpXblpy74o7gUzjxJe3MRwBsBhmuABCIZH5zdNOkwSka324q1wEKwUJYOuGf4ZQwBoQGFsgzagH0Oipf4D8EJl92/F0BAnn07brCUknB19ezaS6EBhbIM2oB9DoqX+A/BCZfdvxdAQJ59O26wlJJwdfXs2kuGWvMQekAAhg+EFz1oAACCBBWMdGVzdCBmb3IgY2xprSSt2Q=="
					}
				}
			)
		*/
	}

	public function spendWTT($address, $amount)
	{	
		$bsConfig		 = (new ConfigModel())-> backendConfig();
		$nodeUrl		 = $bsConfig['backendServiceNode'];
		$compilerUrl	 = $bsConfig['backendCompilerUrl'];
		$wallet		     = $bsConfig['walletPath'];
		$password		 = $bsConfig['walletPassword'];
		$aex9Source		 = $bsConfig['aex9Source'];
		$contractAddress = $bsConfig['WTTContractAddress'];
		$aecli           = "tolink/aecli contract call {$wallet} --password {$password} transfer {$address} {$amount} --contractAddress {$contractAddress} --contractSource {$aex9Source} -u {$nodeUrl} --compilerUrl {$compilerUrl} --json";
		exec($aecli, $arr);
		$json 		= json_decode($arr[0], true);
		$hash 		= $json['hash'];
		return $hash;

		/*
		Array(
			[0] => 
				{
					"hash":"th_sAat9GorvoJi9nAugsXcQzbHHVk2Km2DRyGmVqTZe1iQZV85B",
					"rawTx":"tx_+NsLAfhCuED+8dBwFvgdf7CssO3sQwX3V6dvx26nzb8sp1IJj1jQHFYeXu5idNfdnOPTpRxgIiKwurz/zELxfow/s1d/lHEFuJP4kSsBoQHuYux6APX0Ye2p9Hd2upEkVh5oVHHPmQIMpSEXo1aAOYITmaEFdrBJvhNPKZ4HugB+E9MRdbEhQhZbK8C8ELuJ3Hks79UDhqZiCbcYAAAAgxgX+IQ7msoAtCsRhKFdoSufAKADzHtNR6vsbK4SIqUNsOYT+nuIdCLvdI9wreec/FRIrG+IDeC2s6dj/8CNyfQk",
					"result":{
						"callerId":"ak_2ozG9ap2osrqdpVzrFEKAkZ1kN6qmLP2cwqbpLyp8VzWYcP7fG",
						"callerNonce":5017,
						"contractId":"ct_uGk1rkSdccPKXLzS259vdrJGTWAY9sfgVYspv6QYomxvWZWBM",
						"gasPrice":1000000000,
						"gasUsed":5160,
						"height":447728,
						"log":[
							{
								"address":"ct_uGk1rkSdccPKXLzS259vdrJGTWAY9sfgVYspv6QYomxvWZWBM",
								"data":"cb_Xfbg4g==",
								"topics":[
									"15485047184846566156736396069994907050875216973023180189891727495730853981167",
									"107825241076518197924307295769262578609612324424823189182420118345990033276985",
									"1718226345229036126166762797764485915653507165142631617340346829412418996396",
									"1000000000000000000"
								],
								"name":"Transfer"
							}
						],
						"returnType":"ok",
						"returnValue":"cb_P4fvHVw="
					},
					"txData":{
						"blockHash":"mh_isizZCmMFBZ4aPwc23pZELFqrAcwBsmgm2hNwuSbxojDQZdz5",
						"blockHeight":447728,
						"hash":"th_sAat9GorvoJi9nAugsXcQzbHHVk2Km2DRyGmVqTZe1iQZV85B",
						"signatures":[
							"sg_aMXtj8jYz1xBdi5CVFnaxtQ9fL27i8n9AC8tRrJ4Lywp7YeCMizZfPCiNaeT9iJQtkfV1JrdY5EMZVmNAoegPSHq9BSM4"
						],
						"tx":{
							"abiVersion":3,
							"amount":0,
							"callData":"cb_KxGEoV2hK58AoAPMe01Hq+xsrhIipQ2w5hP6e4h0Iu90j3Ct55z8VEisb4gN4Lazp2P/wHtkQNs=",
							"callerId":"ak_2ozG9ap2osrqdpVzrFEKAkZ1kN6qmLP2cwqbpLyp8VzWYcP7fG",
							"contractId":"ct_uGk1rkSdccPKXLzS259vdrJGTWAY9sfgVYspv6QYomxvWZWBM",
							"fee":182940000000000,
							"gas":1579000,
							"gasPrice":1000000000,
							"nonce":5017,
							"type":"ContractCallTx",
							"version":1
						},
						"callerId":"ak_2ozG9ap2osrqdpVzrFEKAkZ1kN6qmLP2cwqbpLyp8VzWYcP7fG",
						"callerNonce":5017,
						"contractId":"ct_uGk1rkSdccPKXLzS259vdrJGTWAY9sfgVYspv6QYomxvWZWBM",
						"gasPrice":1000000000,
						"gasUsed":5160,
						"height":447728,
						"log":[
							{
								"address":"ct_uGk1rkSdccPKXLzS259vdrJGTWAY9sfgVYspv6QYomxvWZWBM",
								"data":"cb_Xfbg4g==",
								"topics":[
									"15485047184846566156736396069994907050875216973023180189891727495730853981167",
									"107825241076518197924307295769262578609612324424823189182420118345990033276985",
									"1718226345229036126166762797764485915653507165142631617340346829412418996396",
									"1000000000000000000"
								]
							}
						],
						"returnType":"ok",
						"returnValue":"cb_P4fvHVw=",
						"rawTx":"tx_+NsLAfhCuED+8dBwFvgdf7CssO3sQwX3V6dvx26nzb8sp1IJj1jQHFYeXu5idNfdnOPTpRxgIiKwurz/zELxfow/s1d/lHEFuJP4kSsBoQHuYux6APX0Ye2p9Hd2upEkVh5oVHHPmQIMpSEXo1aAOYITmaEFdrBJvhNPKZ4HugB+E9MRdbEhQhZbK8C8ELuJ3Hks79UDhqZiCbcYAAAAgxgX+IQ7msoAtCsRhKFdoSufAKADzHtNR6vsbK4SIqUNsOYT+nuIdCLvdI9wreec/FRIrG+IDeC2s6dj/8CNyfQk"
					},
					"decodedResult":[

					],
					"decodedEvents":[
						{
							"address":"ct_uGk1rkSdccPKXLzS259vdrJGTWAY9sfgVYspv6QYomxvWZWBM",
							"data":"cb_Xfbg4g==",
							"topics":[
								"15485047184846566156736396069994907050875216973023180189891727495730853981167",
								"107825241076518197924307295769262578609612324424823189182420118345990033276985",
								"1718226345229036126166762797764485915653507165142631617340346829412418996396",
								"1000000000000000000"
							],
							"name":"Transfer",
							"decoded":[
								"2ozG9ap2osrqdpVzrFEKAkZ1kN6qmLP2cwqbpLyp8VzWYcP7fG",
								"2g2yq6RniwW1cjKRu4HdVVQXa5GQZkBaXiaVogQXnRxUKpmhS",
								"1000000000000000000"
							]
						}
					]
				}
			)
		*/
	}

}

