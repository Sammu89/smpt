<?php

//Como chamar os campos: [epi num=1 campo=nome_pt] [epi num=1 campo=link_hd] [rmvb1] [link_rmvb1]
//Campos nome_jp; nome_romanji;nome_traduzido;nome_pt;link_hd (os rmvb têm estrutura diferente)


//LINKS DE ALOJAMENTO EXTERNO
function link_externo() {
    return array(
1 => '',
2 => '',
    );
}

// Call como [link_externo 1]
function link_externo_shortcode($atts) {
    // Extract the first parameter from the shortcode attributes
    $atts = array_change_key_case($atts, CASE_LOWER); // Convert all keys to lowercase
    $atts = array_values($atts); // Re-index the array
    
    // Get the first value which will be treated as the ID
    $id = intval($atts[0]);
    
    // Retrieve the URLs array
    $urls = link_externo();
    
    // Return the URL corresponding to the ID or an error message
    if (array_key_exists($id, $urls)) {
        return $urls[$id];
    } else {
        return 'Erro.';
    }
}
add_shortcode('link_externo', 'link_externo_shortcode');

//DADOS DE CADA EPISODIO
$episode_data = array(
    1 => array(
        'nome_jp' => '泣き虫うさぎの華麗なる変身',
        'nome_romanji' => 'Nakimushi Usagi no kareinaru henshin',
        'nome_traduzido' => 'A Mudança Graciosa da Chorona Usagi',
        'nome_pt' => 'A choramingas transforma-se em guerreira',
		'resumo' => 'Enquanto corre para as aulas, uma jovem rapariga chamada Bunny encontra um gato em apuros. Após o salvar, extraordinários eventos acontecem. O começo das aventuras da Navegante da Lua.',
    ),
    2 => array(
        'nome_jp' => 'おしおきよ！占いハウスは妖魔の館',
        'nome_romanji' => 'Oshioki yo!<br>Uranai hausu wa youma no yakata',
        'nome_traduzido' => 'Vou Castigar-te!<br>A Casa da Fortuna é a Mansão de um Youma',
        'nome_pt' => 'A casa da sorte é um ninho de monstros',
		'resumo' => 'Para ganhar coragem para dizer que gosta de Bunny, Jimmy vai à casa da sorte. Após sair de lá o seu comportamento altera-se radicalmente. Luna suspeita que algo está a deturpar os espíritos dos jovens estudantes.',
    ),
		3 => array(
        'nome_jp' => '謎のねむり病、守れ乙女の恋する心',
        'nome_romanji' => 'Nazo no nemuri-byou, mamore otome no koisuru kokoro',
        'nome_traduzido' => 'A Misteriosa Doença do Sono!<br>Proteger o Coração das Raparigas Apaixonadas',
        'nome_pt' => 'Salvem As Raparigas Apaixonadas',
		'resumo' => 'O novo programa de rádio transmite cartas românticas enviadas pelos ouvintes. Em troca das cartas de amor, recebem um alfinete em forma de flôr. É quando as pessoas começam a cair num sono profundo em que nada as acorda.',

    ),
    4 => array(
        'nome_jp' => 'うさぎが教えます！スリムになる法',
        'nome_romanji' => 'Usagi ga oshiemasu!<br>Surimu ni naru hou',
        'nome_traduzido' => 'A Usagi Ensina-te!<br>Como Perder Peso',
        'nome_pt' => 'Gostavas De Ser Magra?',
		'resumo' => 'Um novo ginásio abre em Juuban. As raparigas ficam mais magras a cada sessão de ginástica. Quando Luna vê a Prof. Carolina às portas da morte devido a magreza excessiva, chama Bunny para averiguar.',

    ),
    5 => array(
        'nome_jp' => '妖魔の香り！シャネラーは愛を盗む',
        'nome_romanji' => 'Youma no kaori!<br>Shaneraa wa ai o nusumu',
        'nome_traduzido' => 'O Perfume de um Monstro!<br>A Chanela que Rouba Amor',
        'nome_pt' => 'O Perfume Da Chinchila',
		'resumo' => 'Luna tenta entrosar-se na família Tsukino, mas Chico é contra adoptá-la como animal de estimação. Luna é posta de parte quando Bunny se rende aos encantos do animal, as Chinchilas.',
    ),
    6 => array(
        'nome_jp' => '守れ恋の曲！うさぎはキューピッド',
        'nome_romanji' => 'Mamore koi no kyoku!<br>Usagi wa kyuupiddo',
        'nome_traduzido' => 'Proteger a Melodia do Amor!<br>A Usagi Faz de Cupido',
        'nome_pt' => 'Nunca Desistas Do Amor',
		'resumo' => 'Um envergonhado compositor compõe uma melodia para a sua amada e grava-a em cassete. Quando um terrível monstro o persegue para obtê-la, este choca com Bunny que de imediato tenta fazer com que o casal de junte.',

    ),
    7 => array(
        'nome_jp' => 'うさぎ反省!<br>スターの道はきびしい',
        'nome_romanji' => 'Usagi hansei!<br>Sutaa no michi wa kibishii',
        'nome_traduzido' => 'A Usagi Aprende Uma Lição!<br>O Caminho para o Estrelato é Duro',
		'nome_pt' => 'O longo caminho para a fama',
        'resumo' => 'Um concurso para encontrar talentos vai-se realizar brevemente. Bunny e Sara tentam a sua sorte, mas cedo se zangam. Quando todos os alunos apenas pensam no concurso negligenciando as suas vidas Bunny investiga a situação.',

    ),
    8 => array(
        'nome_jp' => '天才少女は妖魔なの？恐怖の洗脳塾',
        'nome_romanji' => 'Tensai Shoujo wa Youma nano?<br>Kyoufu no Sennoujuku',
        'nome_traduzido' => 'A Rapariga Génio é um Monstro?<br>Escola de Terror de Lavagem Cerebral',
        'nome_pt' => 'Terror No Seminário',
		'resumo' => 'Luna está atento aos demónios do Reino das Trevas. Quando uma rapariga demonstra capacidades intelectuais extraordinárias, está certo de que se trata de mais um plano maquiavélico. Bunny entra em acção para a derrotar.',
    ),
    9 => array(
        'nome_jp' => 'うさぎの災難!<br>あわて時計にご用心',
        'nome_romanji' => 'Usagi no sainan!<br>Awate tokei ni goyōshin',
        'nome_traduzido' => 'O Azar da Usagi!<br>Cuidado com os Relógios Apressados',
        'nome_pt' => 'Cuidado Com Os Relógios',
		'resumo' => 'Bunny quer divertir um pouco a sua nova amiga Am, por isso vão as duas às compras. Para sua surpresa encontram uma loja com relógios muito baratos, que fazem sucesso pela cidade. Quando as pessoas começam a ficar alteradas, Luna suspeita dos famosos relógios.',

    ),
    10 => array(
        'nome_jp' => '呪われたバス！炎の戦士マーズ登場',
        'nome_romanji' => 'Norowareta basu!<br>Honoo no senshi maazu toujou',
        'nome_traduzido' => 'Autocarros Amaldiçoados!<br>A Guerreira do Fogo Marte Aparece',
        'nome_pt' => 'Marte A Guerreira Do Fogo',
		'resumo' => 'Cada vez mais autocarros desaparecem sem deixar rasto na sua ultima paragem, em frente ao templo Hikawa. Quando Bunny vai investigar o templo, encontra uma estranha rapariga que a ataca e a deixa inconsciente…',
    ),
    11 => array(
        'nome_jp' => 'うさぎとレイ対決？夢ランドの悪夢',
        'nome_romanji' => 'Usagi to rei taiketsu?<br>Yume rando no akumu',
        'nome_traduzido' => 'Usagi vs. Rei?<br>Um Pesadelo na Terra dos Sonhos',
        'nome_pt' => 'O Pesadelo Na Terra Dos Sonhos',
		'resumo' => 'Cada vez mais pessoas desaparecem no parque de diversões. A polícia não consegue descobrir o seu paradeiro. Bunny, Rita e Ami entram no parque na esperança de resolver o mistério. Quando Ami fica presa dentro de uma torre, cabe a Rita e Bunny a sua salvação.',
    ),
    12 => array(
        'nome_jp' => '私だって彼が欲しい!<br>豪華船のワナ',
        'nome_romanji' => 'Watashi datte kare ga hoshii!<br>Gouka-sen no wana',
        'nome_traduzido' => 'Eu Também Quero um Namorado!<br>Uma Armadilha num Cruzeiro de Luxo',
        'nome_pt' => 'Gostava De Ter Um Namorado',
		'resumo' => 'Um novo cruzeiro para namorados torna-se popular entre as raparigas. Bunny quer ir abordo a todo o custo, mas é ultrapassada por Rita, que leva Ami na esperança de encontrar um namorado. Quando a energia dos passageiros é roubada o pânico instala-se.',
    ),
    13 => array(
        'nome_jp' => '女の子は団結よ！ジェダイトの最期',
        'nome_romanji' => 'On\'nanoko wa danketsu yo!<br>Jedaito no saigo',
        'nome_traduzido' => 'Poder Feminino!<br>O Fim de Jadeite',
        'nome_pt' => 'O Fim De Jedite',
		'resumo' => 'Jedite faz um ultimato às navegantes: ou elas se revelam e comparecem no aeroporto de Tóquio, ou a cidade será queimada. Arriscando serem descobertas, o que irão as navegantes fazer?',
    ),
    14 => array(
        'nome_jp' => '新たなる強敵、ネフライト魔の紋章',
        'nome_romanji' => 'Aratanaru kyouteki, nefuraito ma no monshou',
        'nome_traduzido' => 'Um Poderoso Novo Inimigo!<br>O Selo Maléfico de Nephrite',
        'nome_pt' => 'Neflite É O Novo E Poderoso Inimigo',
		'resumo' => 'Neflite, o general que sucede a Jedite, decide roubar energia a pessoas específicas quando esras atingem o seu ponto de energia máximo. Entretanto, a melhor amiga de Sara recebe a visita de um homem estranho e o seu comportamento muda radicalmente….',
    ),
    15 => array(
        'nome_jp' => 'うさぎアセる！レイちゃん初デート',
        'nome_romanji' => 'Usagi Aseru!<br>Rei-chan hatsu deeto',
        'nome_traduzido' => 'A Usagi Está Desesperada!<br>O Primeiro Encontro da Rei',
        'nome_pt' => 'A Bunny Entra Em Pânico',
		'resumo' => 'Rita decide ter um encontro romântico com Gonçalo no parque da cidade antes de este fechar definitivamente, Quando o responsável pelo parque adquire o poder de controlar os animais, expulsa as máquinas de demolição. É então que a sua energia é totalmente sugada.',
    ),
    16 => array(
        'nome_jp' => '純白ドレスの夢！うさぎ花嫁になる',
        'nome_romanji' => 'Junpaku doresu no yume!<br>Usagi hanayome ni naru',
        'nome_traduzido' => 'Sonho de um Vestido Branco!<br>A Usagi Torna-se Noiva',
        'nome_pt' => 'A Bunny Transforma-se Numa Noiva',
		'resumo' => 'Bunny decide participar no concurso de noivas da cidade: a rapariga com o melhor vestido tem o seu casamento pago. Quando a sua professora aparece no concurso ataca os concorrentes, é tempo de agir antes que seja tarde demais.',
    ),
    17 => array(
        'nome_jp' => 'モデルはうさぎ？妖魔カメラの熱写',
        'nome_romanji' => 'Moderu wa Usagi?<br>Yōma kamera no nessha',
        'nome_traduzido' => 'A Usagi É uma Modelo?<br>A Fotografia da Câmara do Monstro',
        'nome_pt' => 'A Bunny Será Um Modelo?',
		'resumo' => 'Bunny foi escolhida para fazer parte de uma sessão fotográfica com um famoso fotógrafo. Entretanto Luna descobre que as pessoas fotografadas ficam presas na fotografia. Quando Mercúrio e Marte são capturadas, Bunny fica sozinha para se defender.',
    ),
    18 => array(
        'nome_jp' => '進悟の純情！哀しみのフランス人形',
        'nome_romanji' => 'Shingo no junjou!<br>Kanashimi no Furansu ningyou',
        'nome_traduzido' => 'A Pureza do Shingo!<br>A Boneca Francesa da Tristeza',
        'nome_pt' => 'O Amor Do Chico',
		'resumo' => 'Chico está apaixonado por uma rapariga na sua escola que faz bonecas de porcelana. Após ter partido uma e ter decepcionado a sua colega, ele tenta fazer de tudo para se redimir, mas algo não está certo no comportamento da sua colega…',
    ),
    19 => array(
        'nome_jp' => 'うさぎ感激！タキシード仮面の恋文',
        'nome_romanji' => 'Usagi kangeki!<br>Takishiido kamen no koibumi',
        'nome_traduzido' => 'A Usagi Está em Êxtase!<br>Carta de Amor do Tuxedo Kamen',
        'nome_pt' => 'A Bunny Recebe Uma Carta Do Mascarado',
		'resumo' => 'Neflite mascara-se e enva uma carta de amor em nome do Mascarado a todas as raparigas da cidade. Sara não resiste ao chamamento e depara-se com Nelfite que lhe suga a energia. Apesar de tudo, Sara desenvolve uma paixão mortal por Nelfite.',
    ),
    20 => array(
        'nome_jp' => '夏よ海よ青春よ！おまけに幽霊もよ！',
        'nome_romanji' => 'Usagi kangeki!<br>Takishīdo kamen no koibumi',
        'nome_traduzido' => 'O Verão, o Oceano, a Nossa Juventude!<br>E um Fantasma Também',
        'nome_pt' => 'O Verão O Mar E As Raparigas',
		'resumo' => 'É verão, Bunny, Rita, Ami e Luna decidem passar férias numa velha mansão. Quando um misterioso espectro ameaça a paz das suas férias, é tempo de agir e descobrir a origem do assombro.',
    ),
    21 => array(
        'nome_jp' => '子供達の夢守れ！アニメに結ぶ友情',
        'nome_romanji' => 'Kodomodachi no yume mamore!<br>Anime ni musubu yuujou',
        'nome_traduzido' => 'Proteger os Sonhos das Crianças!<br>Amizade Ligada pelo Anime',
        'nome_pt' => 'Salvem Os Sonhos Das Crianças',
		'resumo' => 'Arranca a produção do novo anime da Sailor V!<br>Uma desenhista sente-se ultrapassada pela sua melhor amiga quanto à qualidade dos seus desenhos. Para a superrar, utiliza um lápis especial. Quando o lápis começa a apoderar-se dela, é tempo das navegantes intervirem.',
    ),
    22 => array(
        'nome_jp' => '月下のロマンス！うさぎの初キッス',
        'nome_romanji' => 'Gekka no romansu!<br>Usagi no hatsu kissu',
        'nome_traduzido' => 'Romance Sob a Lua!<br>O Primeiro Beijo da Usagi',
        'nome_pt' => 'O Primeiro Beijo Da Bunny',
		'resumo' => 'A princesa do Reino do Diamante está de visita a Tóquio. O grande tesouro do reino será revelado. Pensando tratar-se o Cristal Prateado, o Reino das Trevas hipnotiza Sara para roubar o tesouro e apoderar-se da princesa.',
    ),
    23 => array(
        'nome_jp' => '流れ星に願いを！なるちゃんの純愛',
        'nome_romanji' => 'Nagare hoshininegaiwo!<br>Naru-chan no jun\'ai',
        'nome_traduzido' => 'Pedir um Desejo a uma Estrela Cadente!<br>O Amor Puro da Naru',
        'nome_pt' => 'O Primeiro Amor Da Sara',
		'resumo' => 'A paixão de Sara por Neflite é mais evidente do que nunca. Tentando proteger a amiga, Bunny tenta dizer-lhe que ele representa um grande perigo para ela. Não querendo encarar os factos, Sara rouba um cristal da joelheira da mãe e encontra-se com Neflite.',
    ),
    24 => array(
        'nome_jp' => 'なるちゃん号泣！ネフライト愛の死',
        'nome_romanji' => 'Naru-chan goukyuu!<br>Nefuraito ai no shi',
        'nome_traduzido' => 'As Lágrimas da Naru!<br>Nephrite Morre por Amor',
        'nome_pt' => 'O Neflite Morre Pela Sara',
		'resumo' => 'Zocite rapta Sara na esperança que Neflite lhe dê o cristal negro. Neflite acaba por conseguir salvar Sara e apercebe-se que está do lado errado. Quando tudo parece acabar bem, os demónios de Zoicite acabam com a vida de Neflite…',
    ),
    25 => array(
        'nome_jp' => '恋する怪力少女、ジュピターちゃん',
        'nome_romanji' => 'Koisuru kairikishoujo, jupitaa-chan',
        'nome_traduzido' => 'Júpiter, a Poderosa Rapariga Apaixonada',
        'nome_pt' => 'A Poderosa Júpiter Está Apaixonada',
		'resumo' => 'Uma nova rapariga vem para a escola de Bunny. Rapidamente as duas ficam amigas. Quando Zoicite ataca um rapaz e o transforma em demónio, Maria desperta e nasce a navegante de Júpiter. Luna faz aparecer um misterioso ceptro lunar..',
    ),
    26 => array(
        'nome_jp' => 'なるちゃんに笑顔を！うさぎの友情',
        'nome_romanji' => 'Naru chan ni egao o!<br>Usagi no yuujou',
        'nome_traduzido' => 'Trazer um Sorriso à Cara da Naru!<br>A Amizade da Usagi',
        'nome_pt' => 'A Bunny Fez a Sara Sorrir de Novo',
		'resumo' => 'Sara não consegue ultrapassar a morte do seu amado. Quando Bunny e Jimmy a levam para dar um passeio, Zoicite aparece diante os olhos de Sara e transforma um padre num demónio. Um novo amor entre Sara e Jimmy nasce.',
    ),
    27 => array(
        'nome_jp' => '亜美ちゃんへの恋！？未来予知の少年',
        'nome_romanji' => 'Amichan e no koi!<br>?<br>Mirai yochi no shounen',
        'nome_traduzido' => 'Amor para a Ami?!<br>Um Rapaz que Consegue Prever o Futuro',
        'nome_pt' => 'Rapaz Especial Apaixona-se Pela Ami',
		'resumo' => 'Finalmente Ami é ultrapassada nos exames por um rapaz seu apaixonado. Zoicite percebe que ele tem um dos cristais arco-íris ataca-o. Sailor Moon não consegue fazer com que ele volte à forma humana. Será o seu amor por Ami suficiente para as salvar?',
    ),
    28 => array(
        'nome_jp' => '恋のイラスト、うさぎと衛が接近？',
        'nome_romanji' => 'Koi no irasuto, Usagi to Mamoru ga sekkin?',
        'nome_traduzido' => 'Ilustrações do Amor!<br>Estão a Usagi e o Mamoru a Ficar Mais Próximos?',
        'nome_pt' => 'O Desenho Amoroso de Bunny e Gonçalo',
		'resumo' => 'Uma famosa pintora faz uma exposição na cidade. Ninguém conhece a sua identidade. Quando Bunny e Gonçalo são convidados para posar num quadro, algo corre terrivelmente mal.',
    ),
    29 => array(
        'nome_jp' => '大混戦！グチャグチャ恋の四角関係',
        'nome_romanji' => 'Dai konsen!<br>Guchagucha koi no shikaku kankei',
        'nome_traduzido' => 'Caos Total!<br>O Desastrado Quadrado Amoroso',
        'nome_pt' => 'Um Namoro Confuso',
		'resumo' => 'Maria aproxima-se emocionalmente de Mário, o rapaz da casa de jogos. Bunny fica com ciúmes e as duas entram em competição. Quando a namorada de Mário entra em cena, um complicado quadrado amoroso ocorre…',
    ),
    30 => array(
        'nome_jp' => 'お爺ちゃん乱心、レイちゃんの危機',
        'nome_romanji' => 'Ojiichan ranshin, reichan no kiki',
        'nome_traduzido' => 'O Avô Enlouqueceu!<br>A Rei Está em Perigo',
        'nome_pt' => 'O Avô Da Rita Enlouqueceu',
		'resumo' => 'A busca pelos sete cristais arco-íris intensifica-se. Entretanto, um belo rapaz aparece no templo, ganhando o afecto de Rita. O atrevido avô é atacado por Zoicite. Quando este ataca a sua neta, é tempo de Bunyn salvar o dia.',
    ),
    31 => array(
        'nome_jp' => '恋されて追われて！ルナの最悪の日',
        'nome_romanji' => 'Koi sa rete owa rete!<br>Runa no saiaku no hi',
        'nome_traduzido' => 'Amada e Perseguida!<br>O Pior Dia de Sempre da Luna',
        'nome_pt' => 'O Pior Dia Do Luna',
		'resumo' => 'Luna é salvo de uma luta por uma misteriosa gata. Bunny, Maria e Ami seguem uma pista do ceptro lunar para encontrar outro portador de um cristal arco-íris. Quando se apercebem que estão a proteger a pessoa errada, é tarde demais.',
    ),
    32 => array(
        'nome_jp' => '海野の決心！なるちゃんは僕が守る',
        'nome_romanji' => 'Umino no kesshin!<br>Naru-chan wa boku ga mamoru',
        'nome_traduzido' => 'A Decisão do Umino!<br>Vou Proteger a Naru',
        'nome_pt' => 'O Jimmy Decide Proteger a Sara',
		'resumo' => 'Luna revela às navegantes que ele é originário da Lua. Jimmy decide mascarar-se de Mascarado e seguir Sara por todo o lado. Quando eles são atacados, Kimmy tenta em vão defender Sara com a sua vida. Uma paixão começa entre os dois.',
    ),
    33 => array(
        'nome_jp' => '最後のセーラー戦士、ヴィーナス登場',
        'nome_romanji' => 'Saigo no seera senshi, vu~iinasu toujou',
        'nome_traduzido' => 'A Última Sailor Guerreira Vénus Aparece',
        'nome_pt' => 'A Última Navegante - Vénus',
		'resumo' => 'Uma nova Navegante da Lua pareceu na cidade. Mesmo sabendo que se trata de uma cilada, as guerreiras decidem salvá-la das garras de Kunzite. Todas, incluindo o Mascarado, são apanhadas na armadilha. Uma misteriosa guerreira salva as suas vidas.',
    ),
    34 => array(
        'nome_jp' => '光輝く銀水晶！月のプリンセス登場',
        'nome_romanji' => 'Hikari kagayaku ginsuishou!<br>Tsuki no purinsesu toujou',
        'nome_traduzido' => 'O Brilhante Cristal Prateado!<br>A Princesa da Lua Aparece',
        'nome_pt' => 'O Cristal Prateado',
		'resumo' => 'Zoicite desafia Gonçalo para um duelo no qual o vencedor fica com os cristais arco-íris. Bunny segue-o e ambos acabam por descobrir as suas identidades secretas. O Cristal Prateado finalmente aparece, e quando Gonçalo é ferido fatalmente, a Princesa Serenidade desperta.',
    ),
    35 => array(
        'nome_jp' => 'よみがえる記憶！うさぎと衛の過去',
        'nome_romanji' => 'Yomigaeru kioku!<br>Usagi to Mamoru no kako',
        'nome_traduzido' => 'Memórias que Regressam!<br>O Passado da Usagi e Mamoru',
        'nome_pt' => 'As Memórias De Bunny E Gonçalo',
		'resumo' => 'Serenidade começa a lembrar-se da sua vida passada. Gonçalo é raptado pelo Reino das Trevas, enquanto que Zoicite é morto por Beryl, devido aos seus sucessivos falhanços. As guerreiras tentam desesperadamente sair da torre.',
    ),
    36 => array(
        'nome_jp' => 'うさぎ混乱！タキシード仮面は悪？',
        'nome_romanji' => 'Usagi konran!<br>Takishiido kamen wa waru?',
        'nome_traduzido' => 'A Usagi Está Confusa!<br>Será que o Tuxedo Kamen é Mau?',
        'nome_pt' => 'O Mascarado É O Inimigo?',
		'resumo' => 'Joana tenta animar Bunny depois de esta ter perdido o Gonçalo para o Reino das Trevas. Para isso, leva-a ao salão mais famoso da cidade, o cabeleireiro da Quica Madeixa. Enquanto estão a ser tratadas, Joana é atacada sem se poder defender…',
    ),
    37 => array(
        'nome_jp' => 'めざせプリンセス？うさぎの珍特訓',
        'nome_romanji' => 'Mezase purinsesu?<br>Usagi no chin tokkun',
        'nome_traduzido' => 'Ser uma Princesa?<br>O Treino Esquisito da Usagi',
        'nome_pt' => 'A Bunny Ensaia Para Princesa',
		'resumo' => 'Bunny descobre que uma aristocrata inglesa veio administrar um curso para ser princesa em Tóquio. Tentando identificar-se com o seu estatuto, Bunny consegue entrar no curso, mas cedo descobre que não é a sua vocação.',
    ),
    38 => array(
        'nome_jp' => '雪よ山よ友情よ！やっぱり妖魔もよ',
        'nome_romanji' => 'Yuki yo yamayo yuujou yo!<br>Yappari youma mo yo',
        'nome_traduzido' => 'A Neve, as Montanhas, a Nossa Amizade!<br>E Claro, um Youma Também',
        'nome_pt' => 'Viva A Neve As Montanhas E A Amizade',
		'resumo' => 'Rita descobre que o rapaz que trabalha no templo, Fernando, é rico e tem uma casa nas montanhas. Todas vão para lá e passam umas mini-férias. Quando Bunny e Rita entram no concurso de sky Miss princesa da Lua, são apanhadas numa terrível armadilha.',

    ),
    39 => array(
        'nome_jp' => '妖魔とペア！？氷上の女王まこちゃん',
        'nome_romanji' => 'Youma to pea!<br>?<br>Hikami no joou mako-chan',
        'nome_traduzido' => 'O Meu Par é um Youma?<br>Mako, a Rainha do Gelo',
        'nome_pt' => 'A Maria É A Rainha Do Gelo',
		'resumo' => 'Uma nova pista de gelo abre na cidade!<br>Para promover a sua inauguração, um par de famosos patinadores dá aulas gratuitas a todos os que se apresentarem na pista. Maria desde logo capta as atenções de todos, revelando-se uma exímia patinadora.',

    ),
    40 => array(
        'nome_jp' => '湖の伝説妖怪！うさぎ家族のきずな',
        'nome_romanji' => 'Mizuumi no densetsu youkai!<br>Usagi kazoku no kizuna',
        'nome_traduzido' => 'O Lendário Monstro do Lago!<br>A Ligação Familiar da Usagi',
        'nome_pt' => 'O Espírito Mau Do Lago',
		'resumo' => 'Chegaram as férias da família Tsukino!<br>Bunny, os seus pais, o seu irmão Chico partem em direção às termas. Lá, Bunny avista Gonçalo do topo de uma colina. A sua mãe conta-lhe a história do misterioso espírito do lago…',
    ),
  41 => array(
        'nome_jp' => 'もう恋から逃げない！亜美と衛対決',
        'nome_romanji' => 'Mou koi kara nigenai!<br>Ami to Mamoru taiketsu',
        'nome_traduzido' => 'Não Voltarei a Fugir do Amor!<br>O Confronto Entre Ami e o Mamoru',
        'nome_pt' => 'Não Tenho Medo De O Amar',
		'resumo' => 'Endymion anda a recolher novamente os portadores dos Cristais Arco-Íris. Rui, um dos portadores, avisa Ami do propósito do inimigo. Quando este é capturado pelo inimigo Ami enfrenta Endymion com o perigo da sua vida para recuperar Rui.',

    ),
    42 => array(
        'nome_jp' => 'Sヴィーナスの過去、美奈子の悲劇',
        'nome_romanji' => 'Sērā Vīnasu no kako, Minako no higeki',
        'nome_traduzido' => 'O Passado da Sailor Venus. O Trágico Amor da Minako',
        'nome_pt' => 'O Trágico Amor da Joana',
		'resumo' => 'Joana encontra uma velha amiga do seu passado. Quando o Reino das Trevas a transforma num youma Vénus vê-se forçada a enfrentá-la. Uma parte do passado de Joana como Sailor V é desvendado.',

    ),
    43 => array(
        'nome_jp' => 'うさぎが孤立？S戦士達の大ゲンカ',
        'nome_romanji' => 'Usagi ga koritsu?<br>S senshi-tachi no dai genka',
        'nome_traduzido' => 'A Usagi Sozinha?<br>As Sailor Guerreiras Entram Numa Grande Luta',
        'nome_pt' => 'A Navegante Da Lua Está Contra As Guerreiras',
		'resumo' => 'As guerreiras estão contra Sailor Moon!<br>Nos jornais da cidade não se fala noutra coisa. Navegante da Lua decide juntar-se ao Reino das Trevas na esperança de se reencontrar com o Mascarado.',

    ),
    44 => array(
        'nome_jp' => 'うさぎの覚醒！超過去のメッセージ',
        'nome_romanji' => 'Usagi no kakusei!<br>Chou kako no messeeji',
        'nome_traduzido' => 'O Despertar da Usagi!<br>Uma Mensagem do Passado Distante',
        'nome_pt' => 'A Bunny Acorda Com Uma Mensagem Do Passado',
		'resumo' => 'Kunzite teletransporta as navegantes para outra dimensão. O Cristal Prateado guia-as, e estas são transportadas para a lua, onde a rainha Serenidade do passado conta a história do Milénio de Prata.',

    ),
    45 => array(
        'nome_jp' => 'セーラー戦士死す！悲壮なる最終戦',
        'nome_romanji' => 'Seera senshi shisu!<br>Hisounaru saishuu-sen',
        'nome_traduzido' => 'As Sailor Guerreiras Morrem!<br>A Trágica Batalha Final',
        'nome_pt' => 'As Navegantes Morrem Na Batalha',
		'resumo' => 'As guerreiras navegantes teletransportam-se para o Ponto D no pólo Norte, esconderijo do Reino das Trevas. As DD Girls aparecem e enfrentam as guerreiras guardiãs, matando-as uma por uma. Um dos episódios mais marcantes da série.',

    ),
    46 => array(
        'nome_jp' => 'うさぎの想いは永遠に！新しき転生',
        'nome_romanji' => 'Usagi no omoi wa eien ni!<br>Atarashiki tensei',
        'nome_traduzido' => 'O Desejo Eterno da Usagi!<br>Uma Nova Reencarnação',
        'nome_pt' => 'A Bunny Deseja Uma Nova Vida',
		'resumo' => 'Sailor Moon finalmente encontra Endymion, mas este tenta matá-la. Após ser purificado, a Rainha Beryl assassina-o. Com a ajuda do Cristal Prateado e dos espíritos das navegantes, a Princesa Serenidade enfrenta a causa de todo o mal: Metália.',

    ),
    47 => array(
        'nome_jp' => 'ムーン復活！謎のエイリアン出現',
        'nome_romanji' => 'Muun fukkatsu!<br>Nazo no eirian shutsugen',
        'nome_traduzido' => 'A Lua Renasceu!<br>Os Alienígenas Misteriosos Aparecem',
        'nome_pt' => 'O Renascimento Da Sailor Moon',
		'resumo' => 'Um meteoro cai em Tóquio durante a noite. No dia seguinte, aparecem dois novos alunos. Nessa noite, um vampiro aparece na cidade e suga a energia das pessoas. Irão Luna e Artemisa conseguir salvar toda a gente sem despertar as memórias das guerreiras?',

    ),
    48 => array(
        'nome_jp' => '愛と正義ゆえ！セーラー戦士再び',
        'nome_romanji' => 'Ai to seigi-yue!<br>Seeraa senshi futatabi',
        'nome_traduzido' => 'Pelo Amor e Pela Justiça!<br>Sailor Guerreiras Uma Vez Mais',
        'nome_pt' => 'As Guerreiras Navegantes Reúnem-Se',
		'resumo' => 'Após um monstro surgir num estúdio de televisão, Bunny está em apuros pois não tem como se defender. As suas companheiras não se lembram do seu passado como navegantes, o que lhes irá acontecer?',

    ),
    49 => array(
        'nome_jp' => '白いバラは誰に？月影の騎士登場',
        'nome_romanji' => 'Shiroi bara wa dare ni?<br>Tsukikage no kishi toujou',
        'nome_traduzido' => 'Para Quem é a Rosa Branca?<br>O Cavaleiro da Sombra Lunar Aparece',
        'nome_pt' => 'O Cavaleiro Do Luar',
		'resumo' => 'Um amigo de Maria é atacado por um monstro e ferido gravemente. Maria promete vingança. Mais tarde, as navegantes tentam matar o monstro mas sem sucesso, nesse momento aparece o misterioso Cavaleiro do Luar…',

    ),
    50 => array(
        'nome_jp' => 'うさぎの危機！ティアラ作動せず',
        'nome_romanji' => 'Usagi no kiki!<br>Tiara sadou sezu',
        'nome_traduzido' => 'Usagi em Crise!<br>A Tiara Não Funciona',
        'nome_pt' => 'A Bunny Está Confusa',
		'resumo' => 'Um novo salão de jogos virtuais abre. A cidade comparece lá em peso, quando aparece um monstro. Bunny transforma-se, mas descobre que a tiara já não funciona. O seu pai, Chico e Gonçalo estão em perigo, o que irá fazer?',

    ),
    51 => array(
        'nome_jp' => '新しき変身！うさぎパワーアップ',
        'nome_romanji' => 'Atarashiki henshin!<br>Usagi pawaa appu',
        'nome_traduzido' => 'Uma Nova Transformação!<br>O Novo Poder da Usagi',
        'nome_pt' => 'A Transformação Da Bunny',
		'resumo' => 'A escola vai ao parque para festejar o dia das cerejeiras em flor. Algumas pessoas desaparecem e reaparecem sem energia no corpo. Quando Sailor Moon perde o seu poder a situação torna-se desesperante…',

    ),
    52 => array(
        'nome_jp' => '狙われた園児！ヴィーナス大活躍',
        'nome_romanji' => 'Nerawa reta enji!<br>Vu~iinasu dai katsuyaku',
        'nome_traduzido' => 'Crianças do Infantário em Perigo!<br>A Grande Atuação da Venus',
        'nome_pt' => 'Crise No Jardim Infantil',
		'resumo' => 'Joana fica envolvida acidentalmente com uma aluna do jardim de infância que idolatra a Sailor Moon, exatamente quando os alienígenas escolhem como alvo crianças para roubar a sua energia. Vénus é encorajada e ganha um novo poder.',

    ),
    53 => array(
        'nome_jp' => '衛とうさぎのベビーシッター騒動',
        'nome_romanji' => 'Mamoru to Usagi no bebiishittaa soudou',
        'nome_traduzido' => 'O Caos Babysitter do Mamoru e da Usagi',
        'nome_pt' => 'Babysitter Precisa-Se',
		'resumo' => 'Um monstro ataca um jardim infantil e Gonçalo vê-se com uma criança nas mãos, visto que a mãe está no hospital. Bunny ajuda-o na tarefa enquanto tenta que ele recupere a memória. Mas não é a única interessada em Gonçalo…',

    ),
    54 => array(
        'nome_jp' => '文化祭は私のため？！レイ女王熱唱',
        'nome_romanji' => 'Bunkamatsuri wa watashi no tame?!<br>Rei joou nesshou',
        'nome_traduzido' => 'O Festival Cultural é Para Mim!?<br>A Rainha Rei Canta Com Paixão',
        'nome_pt' => 'A Melodia Da Rita',
		'resumo' => 'Rita está a organizar o festival da sua escola. Com todo o seu esforço compõe o festival inteiro. Na altura de brilhar, um monstro aparece e suga a energia a todos, inclusive a energia das navegantes. Cabe a Rita salvar a situação.',

    ),
    55 => array(
        'nome_jp' => '月影は星十郎？もえるまこちゃん',
        'nome_romanji' => 'Tsukikage wa hoshi juurou?<br>Moeru mako-chan',
        'nome_traduzido' => 'O Seijuro é o Cavaleiro da Sombra Lunar?<br>A Mako Apaixona-se',
        'nome_pt' => 'O Amor É',
		'resumo' => 'Maria tenta aproximar-se de Sérgio, que se tornou sua nova paixão, e, no processo, irrita Natália. Enquanto isso, Usagi passa o dia cheia de fome, depois de se esquecer do almoço.',

    ),
    56 => array(
        'nome_jp' => '衛のキス奪え!<br>アンの白雪姫作戦',
        'nome_romanji' => 'Mamoru no kisu ubae!<br>An no shirayukihime sakusen',
        'nome_traduzido' => 'Roubar um Beijo ao Mamoru!<br>A Estratégia Branca de Neve da An',
        'nome_pt' => 'A Operação Branca de Neve da An',
		'resumo' => 'Gonçalo está a ensaiar uma peça de teatro, mas não tem actores. Todas se oferecem para o ajudar. Calha a An o papel de Branca de Neve e esta tenta beijar Gonçalo nos ensaios. Ail está decidido a acabar com a peça…',

    ),
    57 => array(
        'nome_jp' => '放課後にご用心！狙われたうさぎ',
        'nome_romanji' => 'Houkago ni goyoushin!<br>Nerawareta Usagi',
        'nome_traduzido' => 'Cuidado Depois da Escola!<br>A Usagi é o Alvo',
        'nome_pt' => 'Perigo Depois da Escola',
		'resumo' => 'Bunny é apanhada a comer nas aulas e fica de castigo após o fim das aulas com An. A professora Carolina sai para o encontro e esquece-se delas. An está sedenta por energia e Bunny é a única pessoa por perto…',

    ),
    58 => array(
        'nome_jp' => 'すれちがう愛の心！怒りの魔界樹',
        'nome_romanji' => 'Surechigau ai no kokoro!<br>Ikari no makai-ju',
        'nome_traduzido' => 'Os Corações dos Amantes Desentendem-se!<br>A Árvore Makai Está Furiosa',
        'nome_pt' => 'Amor Desencontrado',
		'resumo' => 'Ail e An deixam de ir às aulas. Bunny visita-os e é mal recebida. Entretanto descobre que eles guardam uma estranha árvore cósmica em casa e decide investigar. An prende Bunny com o intuito de a matar.',

    ),
    59 => array(
        'nome_jp' => 'めざめる真実の愛！魔界樹の秘密',
        'nome_romanji' => 'Mezameru shinjitsu no ai!<br>Makai-ju no himitsu',
        'nome_traduzido' => 'O Amor Verdadeiro Desperta!<br>O Segredo da Árvore Makai',
        'nome_pt' => 'O Segredo da Árvore',
		'resumo' => 'Gonçalo e Bunny estão presos na árvore. Esta não obedece a Ail e An e tenta sugar toda a energia de Bunny e Gonçalo. An é morta e todo o segredo que envolve estes aliens é revelado.',

    ),
    60 => array(
        'nome_jp' => '天使？悪魔？空からきた謎の少女',
        'nome_romanji' => 'Tenshi?<br>Akuma?<br>Sora kara kita nazo no shoujo',
        'nome_traduzido' => 'Um Anjo? Um Demónio?<br>A Misteriosa Rapariga do Céu',
        'nome_pt' => 'Apareceu uma Rapariga Misteriosa',
		'resumo' => 'Após terem derrotado o inimigo Bunny e Gonçalo vivem em paz o sua feliz namoro. Quando passeiam no parque algo estranho cai do céu: uma rapariga de cabelos rosa chamada Bunny. Quem será esta misteriosa rapariga?',

    ),
    61 => array(
        'nome_jp' => 'うさぎ大ショック！衛の絶交宣言',
        'nome_romanji' => 'Usagi dai shokku!<br>Mamoru no zekkou sengen',
        'nome_traduzido' => 'Grande Choque Para a Usagi!<br>O Mamoru Termina Tudo',
        'nome_pt' => 'O Gonçalo Quer Acabar O Namoro',
		'resumo' => 'Tudo está a correr bem, mas subitamente Gonçalo quer acabar o namoro com Bunny sem aparente razão. Esta sente-se feia e dirige-se à nova loja de maquilhagem da cidade, quando cai na armadilha montada por Bertierite…',

    ),
    62 => array(
        'nome_jp' => '戦士の友情！さよなら亜美ちゃん',
        'nome_romanji' => 'Senshi no yuujou!<br>Sayonara amichan',
        'nome_traduzido' => 'A Amizade das Sailor Guerreiras!<br>Adeus, Ami-chan',
        'nome_pt' => 'A Amizade das Guerreiras Navegantes',
		'resumo' => 'Ami decide ir estudar para a Alemanha. Artemisa desaparece sem deixar rasto. Quando as navegantes investigam ficam presas na geladaria da Lua Negra. Cabe a Ami decidir se ajuda as amigas ou parte para o seu sonho.',

    ),
    63 => array(
        'nome_jp' => '女は強く美しく！レイの新必殺技',
        'nome_romanji' => 'On\'na wa tsuyoku utsukushiku!<br>Rei no shin hissawwaza',
        'nome_traduzido' => 'As Mulheres Devem Ser Fortes e Bonitas!<br>O Novo Ataque Mortífero da Rei',
        'nome_pt' => 'Força e Beleza para as Raparigas',
		'resumo' => 'O avô de Rita decide sair da monotonia e abre um novo desporto no tempo. Uma bela mulher chamada Karmesite oferece-se como treinadora. Mas algo corre terrivelmente mal. Terá Rita poder suficiente para salvar o seu avô?',

    ),
    64 => array(
        'nome_jp' => '銀水晶を求めて！ちびうさの秘密',
        'nome_romanji' => 'Ginsuishou o motomete!<br>Chibi usa no himitsu',
        'nome_traduzido' => 'À Procura do Cristal Prateado!<br>O Segredo da Chibi-Usa',
        'nome_pt' => 'O Segredo da Chibiusa',
		'resumo' => 'Uma tempestade abate-se sobre Tóquio. Chibi-Usa não aguenta as saudades de casa e tenta regressar ao passado. A sua tentativa falha e além de atrair as Irmãs da Caça, a gravidade desaparece…',

    ),
    65 => array(
        'nome_jp' => '恋の論争！美奈子とまことが対立',
        'nome_romanji' => 'Koi no ronsou!<br>Minako to Makoto ga tairitsu',
        'nome_traduzido' => 'Disputa Pelo Amor!<br>Minako e Makoto Enfrentam-se',
        'nome_pt' => '(sem título oficial)',
		'resumo' => 'Uma nova loja de amuletos abre na cidade, sob a liderança de Petzite e Calaverite. Júpiter e Vénus caem na cilada e vendo-se numa situação de desespero desenvolvem um novo ataque.',

    ),
    66 => array(
        'nome_jp' => 'うさぎの親心？カレーな三角関係',
        'nome_romanji' => 'Usagi no oyagokoro?<br>Karēna sankaku kankei',
        'nome_traduzido' => 'Amor Parental da Usagi?<br>Um Triângulo Amoroso de Caril',
        'nome_pt' => 'O Eterno Triângulo numa Festa',
		'resumo' => 'Chibi-usa precisa de alguém que faça um prato de caril para a sua festa da escola. Bunny e Gonçalo oferecem-se para confeccionar o prato. Quando vão ao supermercado comprar os ingredientes algo estranho acontece…',

    ),
    67 => array(
        'nome_jp' => '海よ島よバカンスよ！戦士の休息',
        'nome_romanji' => 'Umi yo shima yo bakansu yo!<br>Senshinokyuusoku',
        'nome_traduzido' => 'Oceano, Ilha, Férias!<br>O Dia Livre das Guerreiras',
        'nome_pt' => 'Férias numa Ilha Distante',
		'resumo' => 'As guerreiras estão de férias numa ilha paradisíaca. Quando Chibi-Usa desaparece em pleno mar tropical e o vulcão da ilha entra em erupção, as navegantes entram entram em desespero e procuram incessantemente por Chibi-Usa.',

    ),
    68 => array(
        'nome_jp' => 'ちびうさを守れ!<br>10戦士の大激戦',
        'nome_romanji' => 'Chibi usa o mamore: 10 Senshi no dai gekisen',
        'nome_traduzido' => 'Proteger a Chibi-Usa!<br>O Confronto das 10 Guerreiras',
        'nome_pt' => 'Protejam a Rabbit',
		'resumo' => 'Chibi-Usa molha a cama e sente-se envergonhada. Para não ser repudiada foge para um local em construção, e é atacada por todas as irmãs da caça. Uma feroz batalha entre as navegantes e as irmãs decorre, quando Rubi ataca Sailor Moon e Chibi-Usa….',

    ),
    69 => array(
        'nome_jp' => '目覚めよ眠れる美少女！衛の苦悩',
        'nome_romanji' => 'Mezameyo nemureru bishoujo!<br>Mamoru no kunou',
        'nome_traduzido' => 'Acorda, Bela Adormecida!<br>A Angústia do Mamoru',
        'nome_pt' => 'O Pesadelo',
		'resumo' => 'Quando Bunny descobre uma rapariga a passear com Gonçalo, fica deprimida. Quando é atacada por Petzite e Calaverite cai num sono sem fim.',

    ),
    70 => array(
        'nome_jp' => '愛の炎の対決！マーズＶＳコーアン',
        'nome_romanji' => 'Ai no honoo no taiketsu!<br>Maazu VS kooan',
        'nome_traduzido' => 'Batalha das Chamas de Amor!<br>Mars vs. Koan',
        'nome_pt' => 'Navegante de Marte Contra Karmesite',
		'resumo' => 'Rubi encarrega Karmesite de matar Rabbit. Esta mascara-se de vendedora e dirige-se ao templo Hikawa a fim de completar a sua missão. Rita intervém ao socorro de Chibi-Usa e revela a sua identidade a Karmesite.',

    ),
    71 => array(
        'nome_jp' => '友情のため！亜美とベルチェ激突',
        'nome_romanji' => 'Yuujou no tame!<br>Ami to beruche gekitotsu',
        'nome_traduzido' => 'Pela Amizade!<br>Ami e Berthier Enfrentam-se',
        'nome_pt' => 'Ami contra Bertierite',
		'resumo' => 'Bertierite enche de poder negro o edifício onde Ami participa no torneio de xadrez, descobrindo que ela é a Navegante de Mercúrio. Bertierite arma-lhe então uma cilada que pode custar a vida às guerreiras. Mascarado acaba com os seus planos e então ela decide suicidar-se…',

    ),
    72 => array(
        'nome_jp' => '非情のルベウス！悲しみの四姉妹',
        'nome_romanji' => 'Hijou no rubeusu!<br>Kanashimi no yonshimai',
        'nome_traduzido' => 'Rubeus Insensível!<br>As Quatro Irmãs da Tristeza',
        'nome_pt' => 'A Dor de Quatro Irmãs',
		'resumo' => 'Calaverita, Petzite e Rubi não suportam a traição de Karmesite e Bertierite. Quando Rubi dá um ceptro que aumenta os poderes a Petzite, esta decide matar as navegantes juntamente com as suas 3 irmãs. Chibi-Usa descobre as identidades secretas das suas amigas.',

    ),
    73 => array(
        'nome_jp' => 'ＵＦＯ出現！さらわれたセーラー戦士たち',
        'nome_romanji' => 'Yuufou shutsugen!<br>Sarawa reta seera senshi-tachi',
        'nome_traduzido' => 'Um OVNI Aparece!<br>As Sailor Guerreiras São Raptadas',
        'nome_pt' => 'A Ameaça do OVNI',
		'resumo' => 'Esmeralda aparece e diz que Rubi está a ficar sem tempo. Chibi-Usa não suporta o facto de Bunny possuir o Cristal Prateado e decide roubá-lo, impedindo a transformação. Rubi rapta as suas companheiras, enquanto que Bunny observa sem nada poder fazer.',

    ),
    74 => array(
        'nome_jp' => 'ルベウスを倒せ！宇宙空間の決戦',
        'nome_romanji' => 'Rubeusu o taose!<br>Uchuu kuukan no kessen',
        'nome_traduzido' => 'Derrotar o Rubeus!<br>A Batalha Final no Espaço',
        'nome_pt' => 'Luta no Espaço',
		'resumo' => 'Bunny recupera o seu alfinete de transformação. Rubi suga Sailor Moon e Chibi-Usa para dentro da sua nave e leva-as para o espaço. Após uma intensa luta, as navegantes são teletransportadas para a terra e Rubi é entregue à sua morte por Esmeralda.',

    ),
    75 => array(
        'nome_jp' => '謎の新戦士セーラープルート登場',
        'nome_romanji' => 'Nazo no shin senshi seeraapuruuto toujou',
        'nome_traduzido' => 'Uma Misteriosa Nova Guerreira!<br>A Sailor Pluto Aparece',
        'nome_pt' => 'A Nova Navegante de Plutão',
		'resumo' => 'Chibi-Usa está em coma. Nenhum médico descobre a causa. Quando Luna-P se transforma num holograma, uma misteriosa guerreira avisa as navegantes que um monstro está a destruir a mente de Chibi-Usa…',

    ),
    76 => array(
        'nome_jp' => '暗黒の魔力！エスメロードの侵略',
        'nome_romanji' => 'Ankoku no maryoku!<br>Esumeroudo no shinryaku',
        'nome_traduzido' => 'O Poder Mágico da Escuridão!<br>A Invasão da Esmeraude',
        'nome_pt' => 'A Invasão da Esmeralda',
		'resumo' => 'Bunny não suporta a cumplicidade entre Gonçalo e Chibi-Usa, por isso descarrega a sua frustração a comer bolos de uma nova pastelaria. Quando as pessoas se começam a transformar em doce é tempo de agir.',

    ),
    77 => array(
        'nome_jp' => '想いは同じ！うさぎと衛の愛再び',
        'nome_romanji' => 'Omoi wa onaji!<br>Usagi to Mamoru no ai futatabi',
        'nome_traduzido' => 'Sentimentos Partilhados!<br>Usagi e Mamoru Apaixonados Novamente',
        'nome_pt' => 'O Amor Renasce',
		'resumo' => 'Bunny decide fazer uma pulseira do amor, para que de algum modo consiga ganhar de volta a paixão de Gonçalo. Após lutarem contra o monstro de Esmeralda que usava as pulseiras para descarregarpoder negro, o amor entre eles renasce.',

    ),
    78 => array(
        'nome_jp' => 'ヴィーナス美奈子のナース大騒動',
        'nome_romanji' => 'Vu~iinasu Minako no naasu ousoudou',
        'nome_traduzido' => 'O Caos de Enfermagem da Vénus Minako',
        'nome_pt' => 'A Navegante de Vénus Enfermeira Entra em Pânico',
		'resumo' => 'Um surto de gripe assombra a cidade. Os únicos saudáveis são Joana e Chibi-Usa. Quando elas decidem fazer o papel de enfermeiras, o caos instala-se. Quando Joana descobre que a gripe é causada por Esmeralda, a luta começa.',

    ),
    79 => array(
        'nome_jp' => 'アルテミスの冒険！魔の動物王国',
        'nome_romanji' => 'Arutemisu no bouken!<br>Ma no doubutsu oukoku',
        'nome_traduzido' => 'A Aventura do Artemis!<br>O Reino Animal Maléfico',
        'nome_pt' => 'As Aventuras da Artemisa',
		'resumo' => 'Artemisa está decidida a provar a Luna que não é desajeitada. Quando descobre que um abrigo de animais está a ser controlado por Esmeralda, tenta combater sozinha, mas cedo descobre que é demasiado fraca para tal poder…',

    ),
    80 => array(
        'nome_jp' => '恐怖の幻影！ひとりぼっちの亜美',
        'nome_romanji' => 'Kyoufu no gen\'ei!<br>Hitori botchi no Ami',
        'nome_traduzido' => 'Ilusão Assustadora!<br>A Ami Está Só',
        'nome_pt' => 'A Solitária Ami',
		'resumo' => 'Ami consegue uma pontuação quase perfeita no exame. De seguida, os seus colegas acusam-na de copiar o teste. Uma ilusão convence-a de que todos estão contra ela, chegando ao ponto de usar a Ilusão de Água Brilhante contra as suas companheiras.',

    ),
    81 => array(
        'nome_jp' => '暗黒ゲート完成？狙われた小学校',
        'nome_romanji' => 'Ankoku geeto kansei?<br>Nerawareta shougakkou',
        'nome_traduzido' => 'Portal Negro Completo?<br>A Escola Primária Em Perigo',
        'nome_pt' => 'O Portão Negro Estará Completo?',
		'resumo' => 'Após ter estado doente, Chibi-Usa finalmente recupera e volta à escola. Qual é o seu espanto quando descobre que a sua melhor amiga e todos os seus colegas estão numa feroz batalha em plena escola.',

    ),
    82 => array(
        'nome_jp' => '未来への旅立ち！時空回廊の戦い',
        'nome_romanji' => 'Mirai e no tabidachi!<br>Jikuu kairou no tatakai',
        'nome_traduzido' => 'Viagem ao Futuro!<br>Batalha no Corredor do Espaço-Tempo',
        'nome_pt' => 'Partida para o Futuro',
		'resumo' => 'As guerreiras decidem ir ao futuro para ajudar Chibi-Usa. Após Plutão lhes permitir a passagem no corredor do espaço-tempo, Esmeralda ataca-as e faz com que se separem e se percam na imensidão do tempo.',

    ),
    83 => array(
        'nome_jp' => '衝撃の未来！デマンドの黒き野望',
        'nome_romanji' => 'Shougeki no mirai!<br>Demando no kuroki yabou',
        'nome_traduzido' => 'Futuro Chocante!<br>A Ambição Sombria de Demande!',
        'nome_pt' => 'A História da Lua Negra',
		'resumo' => 'Chibi-Usa fica destroçada ao ver a destruição da sua terra natal. Foge e separa-se das navegantes. Na sua primeira aparição, o Rei Endymion revela que Chibi-Usa é filha de Bunny e Gonçalo contando de seguida a história da Lua Negra.',

    ),
    84 => array(
        'nome_jp' => 'ワイズマンの魔手！ちびうさ消滅',
        'nome_romanji' => 'Waizuman no mashu!<br>Chibi usa shoumetsu',
        'nome_traduzido' => 'A Mão Malévola de Wiseman!<br>A Chibi-Usa Desaparece!',
        'nome_pt' => 'O Segredo da Small Lady',
		'resumo' => 'A ambição de Esmeralda sobe, e quando aceita uma coroa de rainha dada pelo Sábio, é transformada num feroz dragão. As navegantes conseguem derrotá-la, causando a sua morte. Entretanto Sábio apanha Chibi-Usa.',

    ),
    85 => array(
        'nome_jp' => '暗黒の女王ブラックレディの誕生',
        'nome_romanji' => 'Ankoku no joou burakku redi no tanjou',
        'nome_traduzido' => 'A Rainha da Escuridão!<br>O Nascimento da Black Lady',
        'nome_pt' => 'O Nascimento da Black Lady',
		'resumo' => 'Sábio faz uma lavagem cerebral a Chibi-Usa, transformando-a em Black Lady. As navegantes regressam do futuro e encontram-na. Após tentativas infrutíferas de a fazer voltar ao normal, Black Lady e Diamante começam a abrir o Portão Negro.',

    ),
    86 => array(
        'nome_jp' => 'サフィール絶命！ワイズマンの罠',
        'nome_romanji' => 'Safiiru zetsumei!<br>Waizuman no wana',
        'nome_traduzido' => 'O Saphir Morre!<br>A Armadilha de Wiseman',
        'nome_pt' => 'O Fim do Safira',
		'resumo' => 'Safira ouve uma conversa, em que Sábio revela os seus verdadeiros planos para a Lua Negra. Para o impedir de os concretizar, rouba o chip central do Cristal Negro. Após ser atacado por Sábio, refugia-se em casa das Irmãs da Caça.',

    ),
    87 => array(
        'nome_jp' => '愛と未来を信じて！うさぎの決心',
        'nome_romanji' => 'Ai to mirai o shinjite!<br>Usagi no kesshin',
        'nome_traduzido' => 'Acreditar no Amor e no Futuro!<br>A Decisão da Usagi',
        'nome_pt' => 'A Revolta do Sábio',
		'resumo' => 'As navegantes entram no cristal do Portão Negro para impedir a sua abertura. Sailor Moon é atacada por Diamante. Após resistir, esta consegue fazer com que Diamante se convença que está do lado errado. Sábio então ataca-o mortalmente.',

    ),
    88 => array(
        'nome_jp' => '光と闇の最終決戦！未来へ誓う愛',
        'nome_romanji' => 'Hikari to yami no saishuu kessen!<br>Mirai e chikau ai',
        'nome_traduzido' => 'Batalha Final Entre Luz e Escuridão!<br>O Amor Jurado ao Futuro',
        'nome_pt' => 'A Batalha Final',
		'resumo' => 'A batalha final onde a Rainha Serenidade desperta, juntamente com o regresso da Chibi-Usa e o Cristal Prateado do Futuro!',

    ),
    89 => array(
        'nome_jp' => 'うさぎ達の決意新しき戦いの序曲',
        'nome_romanji' => 'Usagi-tachi no ketsui atarashiki tatakai no jokyoku',
        'nome_traduzido' => 'A Determinação da Usagi e das Outras!<br>Prelúdio para uma Nova Batalha',
        'nome_pt' => 'Epílogo',
		'resumo' => 'As navegantes discutem entre si sobre qual será a protagonista da snova série  . Um episódio com muitos flashbacks e um preview da série S.',

    ),
    90 => array(
        'nome_jp' => '地球崩壊の予感？謎の新戦士出現',
        'nome_romanji' => 'Chikyuu houkai no yokan?<br>Nazo no shin senshi shutsugen',
        'nome_traduzido' => 'Presságio do Fim do Mundo?<br>As Misteriosas Novas Guerreiras Aparecem',
        'nome_pt' => 'O Início de Outra Batalha',
		'resumo' => 'Depois de ter visões apocalípticas, Rita é atacada por um monstro poderoso. Além de não o conseguir vencer, Sailor Moon perde o poder da transformação.',

    ),
    91 => array(
        'nome_jp' => '愛のロッド誕生うさぎの新変身',
        'nome_romanji' => 'Ai no roddo tanjou Usagi no shin henshin',
        'nome_traduzido' => 'Nascimento do Ceptro do Amor!<br>A Nova Transformação da Usagi',
        'nome_pt' => 'O Nascimento de um Novo Ceptro',
		'resumo' => 'Uma rapariga é atacada por um monstro, mas Bunny não se consegue transformar em Sailor Moon, as Navegantes, vencidas, têm de depender uma vez mais das novas e misteriosas Navegantes.',

    ),
    92 => array(
        'nome_jp' => '素敵な美少年？天王はるかの秘密',
        'nome_romanji' => 'Sutekina bishounen?<br>Ten\'ouharuka no himitsu',
        'nome_traduzido' => 'Um Rapaz Bonito?<br>O Segredo de Haruka Tenou',
        'nome_pt' => 'Ele ou Ela?',
		'resumo' => 'Bunny e Joana ficam atraídas por Haruka Tenou, um rapaz atractivo e sofisticado… ou pelo menos é o que elas pensam.',

    ),
    93 => array(
        'nome_jp' => 'うさぎの憧れ!<br>優美な天才みちる',
        'nome_romanji' => 'Usagi no akogare!<br>Yuubina tensai Michiru',
        'nome_traduzido' => 'Ídolo da Usagi!<br>A Génio Graciosa Michiru',
        'nome_pt' => 'O Ídolo é a Mariana',
		'resumo' => 'Com medo de não ser suficientemente inteligente e sofisticada para Gonçalo, Bunny procura aconselhar-se com Mariana.',

    ),
    94 => array(
        'nome_jp' => '純な心を守れ！敵味方三つ巴乱戦',
        'nome_romanji' => 'Jun\'na kokoro o mamore!<br>Teki mikata mitsudomoe ransen',
        'nome_traduzido' => 'Proteger o Coração Puro!<br>A Batalha de Três Frentes',
        'nome_pt' => 'O Primeiro Beijo',
		'resumo' => 'A amiga de Bunny, Fernanda, é atacada por um monstro e vê o seu Coração Puro roubado. Uma corrida contra o tempo começa para encontrar o coração e o devolver, antes que Fernanda perca a vida.',

    ),
    95 => array(
        'nome_jp' => '恋のおたすけはムーンにおまかせ',
        'nome_romanji' => 'Koi no o tasuke wa muun ni omakase',
        'nome_traduzido' => 'Deixa a Lua Ajudar-te no Amor',
        'nome_pt' => 'Profunda Afeição',
		'resumo' => 'Sara e Jimmy entram num concurso para namorados para provar o seu amor, apenas para serem atacados por um monstro de Kaorinite.',

    ),
    96 => array(
        'nome_jp' => '冷酷なウラヌス？まことのピンチ',
        'nome_romanji' => 'Reikokuna uranusu?<br>Makoto no pinchi',
        'nome_traduzido' => 'Uranus Insensível?<br>A Makoto em Perigo',
        'nome_pt' => 'A Maria Está em Perigo',
		'resumo' => 'Maria é atacada por um monstro, mas consegue fugir. Ansiosa por analisar o Coração Puro de Maria, Haruka aproxima-se cada vez mais dela.',

    ),
    97 => array(
        'nome_jp' => '水野ラビリンス！ねらわれた亜美',
        'nome_romanji' => 'Mizuno rabirinsu!<br>Nerawa reta Ami',
        'nome_traduzido' => 'Labirinto de Água!<br>A Ami é o Alvo',
        'nome_pt' => 'O Labirinto Aquático',
		'resumo' => 'O desejo de Ami por evitar uma competição ofende Mariana durante uma corrida de natação. Entretanto um monstro selecciona-a como alvo.',

    ),
    98 => array(
        'nome_jp' => '友達を救え！ムーンウラヌス連合',
        'nome_romanji' => 'Tomodachi wo Sukue!<br>Muun Uranusu Rengou',
        'nome_traduzido' => 'Salvar os Amigos!<br>Moon e Uranus Trabalham Juntas',
        'nome_pt' => 'Todos em União',
		'resumo' => 'Após a Navegante de Neptuno ser capturada e ter sido provavelmente morta por Kaorinite, Sailor Moon e Navegante de Úrano vêm-se presas juntas e são forçadas a reavaliar as suas atitudes.',

    ),
    99 => array(
        'nome_jp' => '男の優しさ！雄一郎レイに失恋？',
        'nome_romanji' => 'Otoko no yasashi-sa!<br>Yuuchirou Rei ni shitsuren?',
        'nome_traduzido' => 'A Bondade de um Homem!<br>Yuichiro de Coração Partido Pela Rei?',
        'nome_pt' => 'Mal de Amor',
		'resumo' => 'Preocupado com a meditação intensiva de Rita, Fernando tem medo que ela se tenha apaixonado por outro homem. Quando vê Haruka, fica convencido que ela é o novo pretendente de Rita.',

    ),
    100 => array(
        'nome_jp' => 'Ｓ戦士を辞めたい！？美奈子の悩み',
        'nome_romanji' => 'Seeraa Senshi wo Yametai!?<br>Minako no Nayami',
        'nome_traduzido' => 'Desistir de Ser Uma Sailor Guerreira?!<br>As Preocupações da Minako',
        'nome_pt' => 'Felicidade Individual',
		'resumo' => 'Joana desencanta-se com a sua vida como Guerreira Navegante. Quando um velho amigo é atacado por um monstro, têm de usar toda a sua perícia para proteger o Coração Puro.',

    ),
    101 => array(
        'nome_jp' => 'うさぎ涙！誕生日にガラスの靴を',
        'nome_romanji' => 'Usagi Namida!<br>Tanjoubi ni Garasu no Kutsu wo',
        'nome_traduzido' => 'Usagi em Lágrimas!<br>Sapatinhos de Vidro Para o Meu Aniversário',
        'nome_pt' => 'O Sapatinho de Cristal',
		'resumo' => 'Bunny fica furiosa quando Gonçalo se esquece do seu aniversário. Quando este lhe oferece uns caros sapatinhos de cristal para a compensar, falha em perceber que estes estavam infectados com um ovo do demónio.',

    ),
    102 => array(
        'nome_jp' => '奪われた純な心！うさぎ絶体絶命',
        'nome_romanji' => 'Ubawareta pyua na kokoro!<br>Usagi zettai zetsumei',
        'nome_traduzido' => 'Coração Puro Roubado!<br>A Usagi Está em Apuros',
        'nome_pt' => 'O Sapatinho de Cristal (2ª parte)',
		'resumo' => 'Com o seu alfinete de transformação roubado e o Mascarado estando raptado, Bunny enfrenta Kaorinite na Torre de Tóquio.',

    ),
    103 => array(
        'nome_jp' => 'やって来たちっちゃな美少女戦士',
        'nome_romanji' => 'Yatte Kita Chicchana Bishoujo Senshi',
        'nome_traduzido' => 'A Chegada de Uma Pequena Sailor Guerreira',
        'nome_pt' => 'A Pequena Guerreira Chibi Moon',
		'resumo' => 'Quando uma percussionista é atacada no Festival de Juban , Sailor Moon e Navegante de Marte recebem ajuda de uma pessoa inesperada.
',

    ),
    104 => array(
        'nome_jp' => '友達を求めて！ちびムーンの活躍',
        'nome_romanji' => 'Tomodachi wo Motomete!<br>Chibi Muun no Katsuyaku',
        'nome_traduzido' => 'Fazer Novos Amigos!<br>A Aventura da Chibi Moon',
        'nome_pt' => 'Procurando Uma Amiga',
		'resumo' => 'Chibi-Usa procura por amigos no Séc. XX, e fica intrigada por umajovem que pratica a cerimónia do chá.
',

    ),
    105 => array(
        'nome_jp' => '力が欲しい！まこちゃんの迷い道',
        'nome_romanji' => 'Pawaa ga Hoshii!<br>Mako-chan no Mayoi Michi',
        'nome_traduzido' => 'Preciso de Poder!<br>Makoto Perdida na Incerteza',
        'nome_pt' => 'Não Estás Só',
		'resumo' => 'Frustrada por não conseguir derrotar os inimigos sozinha, Maria vai treinar para as montanhas com um monge budista.
',

    ),
    106 => array(
        'nome_jp' => '運命のきずな！ウラヌスの遠い日',
        'nome_romanji' => 'Unmei no kizuna!<br>Uranusu no toui hi',
        'nome_traduzido' => 'Vínculo do Destino!<br>Os Dias Distantes da Uranus',
        'nome_pt' => 'Conhecimento Fatal',
		'resumo' => ' Quando uma velha amiga é atacada, Haruka lembra-se de como conheceu Mariana e despertou como Navegante de Úrano.
',

    ),
    107 => array(
        'nome_jp' => '運命のきずな！ウラヌスの遠い日',
        'nome_romanji' => 'Geijutsu wa ai no bakuhatsu!<br>Chibi-Usa no hatsukoi',
        'nome_traduzido' => 'A Arte é uma Explosão de Amor!<br>O Primeiro Amor da Chibi-Usa',
        'nome_pt' => 'A Pequena Artista',
		'resumo' => 'Chibi-Usa apaixona-se por um rapaz da sua turma de arte. Contudo, este parece mais interessado em Mariana.
',

    ),
    108 => array(
        'nome_jp' => 'うさぎのダンスはワルツに乗って',
        'nome_romanji' => 'Usaginodansu wa warutsu ni notte',
        'nome_traduzido' => 'Usagi Dançando a Valsa',
        'nome_pt' => 'Dança Dança Dança!',
		'resumo' => 'Bunny e as outras são convidadas para uma festa para estudantes universitários estrangeiros, mas o seu entusiasmo é arrefecido quando sabe que a maior parte da conversa será feita em Inglês.
',

    ),
    109 => array(
        'nome_jp' => '衝撃の刻！明かされた互いの正体',
        'nome_romanji' => 'Shougeki no koku!<br>Akasa reta tagai no shoutai',
        'nome_traduzido' => 'Momento Chocante!<br>Identidades de Todas Reveladas',
        'nome_pt' => 'Reflexão',
		'resumo' => 'Joana vê que foi a única Navegante que não teve o seu Coração Puro atacado, e tem atitudes cada vez mais absurdas para provar a sua pureza.
',

    ),
    110 => array(
        'nome_jp' => 'ウラヌス達の死 タリスマン出現',
        'nome_romanji' => 'Uranusu-tachi no shi tarisuman shutsugen',
        'nome_traduzido' => 'Morte de Uranus e Neptune?<br>Os Talismãs Aparecem',
        'nome_pt' => 'A Maldição',
		'resumo' => 'Haruka e Mariana são contactadas por Eugénia, que diz que encontrou o portador de um talismã.
',

    ),
    111 => array(
        'nome_jp' => '聖杯の神秘な力！ムーン二段変身',
        'nome_romanji' => 'Seihai no shinpina chikara!<br>Muun nidan henshin',
        'nome_traduzido' => 'O Poder Místico do Cálice Sagrado!<br>Dupla Transformação Lunar',
        'nome_pt' => 'O Santo Graal',
		'resumo' => 'Com as Navegantes de Úrano e Neptuno a morrer, Sailor Moon apressa-se para tirar os talismãs de Eugénia.
',

    ),
    112 => array(
        'nome_jp' => '真の救世主は誰？光と影のカオス',
        'nome_romanji' => 'Shin no kyuuseishu wa dare?<br>Hikatokage no kaosu',
        'nome_traduzido' => 'Quem é o Verdadeiro Messias?<br>Luz e Sombra no Caos',
        'nome_pt' => 'Luz e Sombra',
		'resumo' => 'Bunny e as outras vão para o parque para ver a filmagem de um filme. Enquanto Chibi-Usa trava amizada com a misteriosa Octávia, as filmagens são interrompidas por um novo inimigo, Mimi.
',

    ),
    113 => array(
        'nome_jp' => '妖気漂う家！美少女ほたるの秘密',
        'nome_romanji' => 'Youki tadayou ie!<br>Bishoujo hotaru no himitsu',
        'nome_traduzido' => 'Casa Cheia de Espíritos Malignos!<br>O Segredo da Bela Hotaru',
        'nome_pt' => 'O Segredo da Octávia',
		'resumo' => 'Bunny acompanha Chibi-Usa numa visita à casa da sua nova amiga Octávia, enquanto Mimi ataca um famoso desenhador de manga.
',

    ),
    114 => array(
        'nome_jp' => 'アイドル大好き！悩めるミメット',
        'nome_romanji' => 'Aidoru daisuki nayameru Mimetto',
        'nome_traduzido' => 'Adoro Ídolos!<br>O Dilema de Mimete',
        'nome_pt' => 'Má Sorte Para a Mimi',
		'resumo' => 'Mimi infiltra-se numa competição de canto, para se aproximar do seu alvo, um cantor famoso. Mas quando se qualifica para a ronda final, considera em desistir dos Devoradores da Morte.
',

    ),
    115 => array(
        'nome_jp' => '沈黙の影！？あわき蛍火のゆらめき',
        'nome_romanji' => 'Chinmoku no kage!<br>Awaki hotarubi no yurameki',
        'nome_traduzido' => 'Sombra do Silêncio?!<br>O Ténue Brilho de um Pirilampo',
        'nome_pt' => 'A Sombra do Silêncio',
		'resumo' => 'Octávia esforça-se demais e é levada para o hospital, enquanto Mimi ataca um actor de TV.
',

    ),
    116 => array(
        'nome_jp' => '嵐のち晴れ！ほたるに捧げる友情',
        'nome_romanji' => 'Arashi nochihare!<br>Hotaru ni sasageru yuujou',
        'nome_traduzido' => 'Bonança Depois da Tempestade!<br>Uma Amizade Dedicada à Hotaru',
        'nome_pt' => 'Depois da Tempestade',
		'resumo' => 'Chibi-Usa convida Octávia para um picnic no Jardim Botânico, sem se aperceber que o botânico é o próximo alvo de Mimi.
',

    ),
    117 => array(
        'nome_jp' => 'より高くより強く！うさぎの応援',
        'nome_romanji' => 'Yori takaku yori tsuyoku!<br>Usagi no ouen',
        'nome_traduzido' => 'Mais Alto e Mais Forte!<br>A Usagi Dá-te Força',
        'nome_pt' => 'Muitos Parabéns!',
		'resumo' => 'Bunny e Chibi-Usa acompanham Octávia a um estádio para entregar uma carta a um famoso atleta.
',

    ),
    118 => array(
        'nome_jp' => '魔空の戦い!セーラー戦士の賭け',
        'nome_romanji' => 'Ma sora no tatakai!<br>Seera senshi no kake',
        'nome_traduzido' => 'Batalha Numa Dimensão Maléfica!<br>A Aposta das Sailor Guerreiras',
        'nome_pt' => 'Espaço Alterado',
		'resumo' => 'Quando Mimi decide ajudar na produção de um demónio, ela cria acidentalmente uma anomalia que fractura as dimensões em casa dos Tomoe, prendendo Chibi-Usa e Octávia.
',

    ),
    119 => array(
        'nome_jp' => '沈黙のメシアの覚せい？運命の星々',
        'nome_romanji' => 'Chinmoku no meshia no kakusei?<br>Unmei no shinshin',
        'nome_traduzido' => 'O Messias do Silêncio Despertou?<br>O Destino das Estrelas',
        'nome_pt' => 'O Destino das Estrelas',
		'resumo' => 'Chibi-Usa convida Octávia para um espectáculo no planetário, enquanto Mimi planeia roubar o Coração Puro do DJ.
',

    ),
    120 => array(
        'nome_jp' => '異次元からの侵略！無限学園の謎',
        'nome_romanji' => 'I jigen kara no shinryaku!<br>Mugen gakuen no nazo',
        'nome_traduzido' => 'Invasão de Outra Dimensão!<br>O Mistério do Instituto Mugen',
        'nome_pt' => 'O Mistério da Escola Tecnológica',
		'resumo' => 'Após a família Tomoe desaparecer de sua casa, as Guerreiras Navegantes infiltram-se na Escola Mugen, suspeitando que esta está relacionada com os Devoradores da Morte.
',

    ),
    121 => array(
        'nome_jp' => '心を奪う妖花！第三の魔女テルル',
        'nome_romanji' => 'Kokoro o ubau youka!<br>Dai san no majo Teruru',
        'nome_traduzido' => 'A Mística Flor Que Rouba Corações!<br>Tellu, A Terceira Bruxa',
        'nome_pt' => 'A Planta-Monstro',
		'resumo' => 'A Marilu abre uma loja que vende plantas concebidas para roubar os cristais de corações puros. Entretanto, a Octávia acorda, confusa, na câmara do Messias do Silêncio.
',

    ),
    122 => array(
        'nome_jp' => '愛を信じて！心優しき戦士',
        'nome_romanji' => 'Ai o shinjite!<br>Kokoro yasashiki senshi',
        'nome_traduzido' => 'Acreditar no Amor!<br>Ami, A Bondosa Guerreira',
        'nome_pt' => 'Acreditar no Amor',
		'resumo' => 'Com a última ronda de exames a ser realizada na Escola Mugen, as guerreiras navegantes infiltram-se na escola e a Ami encontra a sua rival académica, Beatriz.
',

    ),
    123 => array(
        'nome_jp' => '破滅の影！沈黙のメシアの目覚め',
        'nome_romanji' => 'Hametsu no kage!<br>Chinmoku no meshia no mezame',
        'nome_traduzido' => 'Sombra da Destruição!<br>O Messias do Silêncio Desperta',
        'nome_pt' => 'O Despertar',
		'resumo' => 'Sílvia, a última das Bruxas 5, invoca os estudantes da Escola Mugen para para lhes remover os seus cristais de coração puro, enquanto que Kaolinite toma Chibi-Usa como o seu alvo.
',

    ),
    124 => array(
        'nome_jp' => '迫り来る闇の恐怖！苦戦の8戦士',
        'nome_romanji' => 'Semari kuru yami no kyōfu!<br>Kusen no 8 senshi',
        'nome_traduzido' => 'O Terror da Escuridão Aproxima-se!<br>Oito Guerreiras em Apuros',
        'nome_pt' => 'O Terror do Silêncio',
		'resumo' => 'As guerreiras navegantes vão à Escola Mugen para salvar a Chibi-Usa e derrotar os Caçadores da Morte, enquanto a Mistress 9 planeia roubar o Santo Graal à Sailor Moon.
',

    ),
    125 => array(
        'nome_jp' => '輝く流星！サターンそして救世主',
        'nome_romanji' => 'Kagayaku ryuusei!<br>Sataan soshite kyuuseishu',
        'nome_traduzido' => 'Estrela Cadente Brilhante!<br>Saturn e o Messias',
        'nome_pt' => 'O Mestre',
		'resumo' => 'A Mistress 9 rouba o Santo Grall, permitindo que o Faraó 90 passe através do portal para a Terra. O destino do mundo fica nas mãos da Guerreira da Destruição, Navegante de Saturno.
',

    ),
    126 => array(
        'nome_jp' => '新しき生命！運命の星々別離の時',
        'nome_romanji' => 'Atarashiki seimei!<br>Unmei no shinshin betsuri no toki',
        'nome_traduzido' => 'Nova Vida!<br>Despedida das Estrelas do Destino',
        'nome_pt' => 'Vida Nova',
		'resumo' => 'As Almas Penadas foram derrotadas, no entanto, o paradeiro de Octávia e da Navegante de Plutão permanece desconhecido. Entretanto, a Haruka e a Mariana têm uma última missão antes de partirem de Juban .
',

    ),
    127 => array(
        'nome_jp' => '戦士の自覚！強さは純な心の中に',
        'nome_romanji' => 'Senshi no jikaku!<br>Tsuyo-sa wa jun\'na kokoronouchi ni',
        'nome_traduzido' => 'Consciência de Uma Guerreira!<br>A Força Está no Coração Puro',
        'nome_pt' => 'O Segredo da Tenacidade',
		'resumo' => 'Enquanto a Chibi-Usa se prepara para voltar ao século XXX, um ovo de demónio sobrevivente surge nas ruínas da Escola Mugen.
',

    ),
    128 => array(
        'nome_jp' => '運命の出会い！ペガサスの舞う夜',
        'nome_romanji' => 'Unmeinodeai!<br>Pegasasu no mau yoru',
        'nome_traduzido' => 'Encontro do Destino!<br>A Noite em que o Pégaso Dança',
        'nome_pt' => 'Encontro Fatal',
		'resumo' => 'Durante um eclipse solar, uma misteriosa tenda de circo voadora aparece no céu de Tóquio. Entretanto, Chibiusa está fascinada por um Pégasus que lhe aparece num sonho.
',

    ),
    129 => array(
        'nome_jp' => 'スーパー変身再び！ペガサスの力',
        'nome_romanji' => 'Suupaa henshin futatabi!<br>Pegasasu no chikara',
        'nome_traduzido' => 'Super Transformação Novamente!<br>O Poder do Pégaso',
        'nome_pt' => 'O Poder de Pégasus',
		'resumo' => 'Quando a Reika volta de África, a Bunny fica preocupada com os problemas da relação entre ela e o Mário. Entretanto, Olho de Tigre escolhe Reika como seu próximo alvo.
',

    ),
    130 => array(
        'nome_jp' => '守れ母の夢！Ｗムーンの新必殺技',
        'nome_romanji' => 'Mamore haha no yume!<br>W muun no shin hissawwaza',
        'nome_traduzido' => 'Proteger o Sonho da Mãe!<br>O Novo Ataque da Dupla Lua',
        'nome_pt' => 'O Sonho da Mãe',
		'resumo' => 'Quando a mãe de Bunny é atacada por Olho de Falcão, a Sailor Moon tem de novo de contar com a ajuda do misterioso Pégasus.
',

    ),
    131 => array(
        'nome_jp' => 'ペガサスを捕えろ！アマゾンの罠',
        'nome_romanji' => 'Pegasasu o toraero!<br>Amazon no wana',
        'nome_traduzido' => 'Capturar o Pégaso!<br>A Armadilha do Trio Amazonas',
        'nome_pt' => 'Uma Armadilha Para Pégasus',
		'resumo' => 'Quando o Olho de Falcão anuncia que criou uma armadilha para apanhar o Pégasus, Olho de Tigre aproxima-se de Sara, com a intenção de a usar como isco.
',

    ),
    132 => array(
        'nome_jp' => 'お似合いの二人！うさぎと衛の愛',
        'nome_romanji' => 'Oniai no futari!<br>Usagi to Mamoru no ai',
        'nome_traduzido' => 'Feitos Um Para o Outro!<br>O Amor Entre Usagi e Mamoru',
        'nome_pt' => 'O Par Perfeito',
		'resumo' => 'A Chibiusa preocupa-se que Saori, uma amiga de Gonçalo da sua universidade, possa estar interessada do ponto de vista romântico nele.
',

    ),
    133 => array(
        'nome_jp' => 'アルテミスの浮気？謎の子猫登場',
        'nome_romanji' => 'Arutemisu no uwaki?<br>Nazo no koneko toujou',
        'nome_traduzido' => 'O Artemis Traiu?<br>Uma Gatinha Misteriosa Aparece',
        'nome_pt' => 'Uma Gatinha Zu-Zum',
		'resumo' => 'Rumores acerca de que a Artemisa possa estar interessada noutros gatos surgem e ficam fora de controlo quando uma pequena gatinha aparece e afirma ser filha de Artemisa.
',

    ),
    134 => array(
        'nome_jp' => 'まことの友情！天馬に憧れた少女',
        'nome_romanji' => 'Makoto no yujou!<br>Tenba ni akogareta shoujo',
        'nome_traduzido' => 'Amizade da Makoto!<br>Uma Rapariga Admiradora do Pégaso',
        'nome_pt' => 'O Apoio de uma Amiga',
		'resumo' => 'Bunny e Chibiusa ficam excitadas quando descobrem que a autora de um best-seller acerca de Pégasus é uma velha amizade de Maria.
',

    ),
    135 => array(
        'nome_jp' => '触れ合う心！ちびうさとペガサス',
        'nome_romanji' => 'Fureau Kokoro!<br>Chibiusa to Pegasasu',
        'nome_traduzido' => 'Corações Que Comunicam!<br>Chibi-Usa e Pégaso',
        'nome_pt' => 'Coração Com Coração',
		'resumo' => 'Olho-de-tigre escolhe como seguinte alvo a professora de artes da Chibi-Usa na esperança de conseguir atacar Pégasus e descobrir qual a pessoa em cujo sonho ele se esconde.
',

    ),
    136 => array(
        'nome_jp' => '衛を守れ！忍者うさぎのヤキモチ',
        'nome_romanji' => 'Mamoru o mamore!<br>Ninja Usagi no yakimochi',
        'nome_traduzido' => 'Proteger o Mamoru!<br>Ninja Usagi Ciumenta',
        'nome_pt' => 'O Passado da Rita',
		'resumo' => 'O Gonçalo muda-se para o Templo Hikawa depois do seu apartamento ficar feito em cacos. Assim, a Bunny disfarça-se de ninja para proteger a honra dele.
',

    ),
    137 => array(
        'nome_jp' => 'あやかしの森！美しき妖精の誘い',
        'nome_romanji' => 'Ayakashi no mori!<br>Utsukushiki yousei no sasoi',
        'nome_traduzido' => 'Floresta de Ilusões!<br>O Convite de Uma Bela Fada',
        'nome_pt' => 'A Fada Flor',
		'resumo' => 'Chibi-Usa torna-se amiga de Kitakata, um desenhador obcecado com fadas. Entretanto, Olho de Peixe prepara-se para o seduzir, disfarçando-se de uma bela fada.
',

    ),
    138 => array(
        'nome_jp' => '天国まで走れ！夢の車にかける愛',
        'nome_romanji' => 'Tengoku made hashire!<br>Yume no kuruma ni kakeru ai',
        'nome_traduzido' => 'Corrida até ao Céu!<br>Carro dos Sonhos Cheio de Amor',
        'nome_pt' => 'Corre Para o Céu',
		'resumo' => 'Ami trava amizade com uma amiga viúva de Gonçalo, e ajuda-a na tarefa de consertar um carro, o sonho que partilhava com o falecido marido. Quando Olho de Falcão as intercepta, a situação complica-se.
',

    ),
    139 => array(
        'nome_jp' => '目指せ日本一！美少女剣士の悩み',
        'nome_romanji' => 'Mezase nihon\'ichi!<br>Bishoujo kenshi no nayami',
        'nome_traduzido' => 'Ser a Mais Forte do Japão!<br>As Preocupações de Uma Bela Espadachim',
        'nome_pt' => 'O Sonho de uma Linda Espadachim',
		'resumo' => 'Uma jovem rapariga com o sonho de se tornar a mais forte espadachim do Japão é o próximo alvo de Olho-de-Tigre.
',

    ),
    140 => array(
        'nome_jp' => 'ミニが大好き！おしゃれな戦士達',
        'nome_romanji' => 'Mini ga daisuki!<br>Osharena senshi-tachi',
        'nome_traduzido' => 'Adoramos Essas Minissaias!<br>Guerreiras na Moda',
        'nome_pt' => 'Uma Moda de Sonho',
		'resumo' => 'Olho de Peixe tem como próximo alvo o estilista Yoshiki Usui, enquanto a Bunny tem esperança de conseguir que o mesmo lhe faça um vestido de noiva de graça.
',

    ),
    141 => array(
        'nome_jp' => '恋の嵐！美奈子のフタマタ大作戦',
        'nome_romanji' => 'Koi no arashi!<br>Minako no futamata dai sakusen',
        'nome_traduzido' => 'Tempestade de Amor!<br>A Grande Estratégia da Minako para Dois Relacionamentos',
        'nome_pt' => 'Tempestade de Amor',
		'resumo' => 'Bunny e as outras ficam chocadas quando descobrem que Joana está a namorar dois rapazes ao mesmo tempo. Apesar de tudo, ninguém desconfia que os dois pretendentes são na realidade Olho de Tigre e Olho de Falcão.
',

    ),
    142 => array(
        'nome_jp' => '秘密の館！愛のメニューを貴方に',
        'nome_romanji' => 'Himitsu no yakata!<br>Ai no menyuu o anata ni',
        'nome_traduzido' => 'Mansão dos Segredos!<br>Um Menu Cheio de Amor Para Ti',
        'nome_pt' => 'A Melhor Receita',
		'resumo' => 'As meninas ficam curiosas acerca de uma senhora idosa aparentemente anti-social, depois de ela trazer Diana para sua casa.
',

    ),
    143 => array(
        'nome_jp' => '天馬を信じる時！4戦士の超変身',
        'nome_romanji' => 'Tenba o shinjiru toki!<br>4 Senshi no chou henshin',
        'nome_traduzido' => 'Acreditem no Cavalo Alado!<br>A Super Transformação das Quatro Guerreiras',
        'nome_pt' => 'A Confiança',
		'resumo' => 'Começam a surgir reportagens acerca de um Pégasus que causa estragos e acidentes na cidade. Entretanto, um rapaz popular mostra interesse pela Chibi-Usa.
',

    ),
    144 => array(
        'nome_jp' => 'きらめく夏の日！潮風の少女亜美',
        'nome_romanji' => 'Kirameku natsu no hi!<br>Shiokaze no shoujo Ami',
        'nome_traduzido' => 'Dias de Verão Brilhantes!<br>Ami, a Rapariga Com a Brisa do Mar',
        'nome_pt' => 'Sonho de Verão',
		'resumo' => 'Chico desenvolve uma paixoneta por Ami durante uma viagem à praia, enquanto Olho-de-tigre organiza um plano para a seduzir.
',

    ),
    145 => array(
        'nome_jp' => 'プリマをねらえ！うさぎのバレエ',
        'nome_romanji' => 'Purima o nerae!<br>Usagi no baree',
        'nome_traduzido' => 'Tornar-se Numa Bailarina Principal!<br>Usagi no Ballet',
        'nome_pt' => 'A Estrela',
		'resumo' => 'A Bunny começa a ter aulas de ballet na esperança de se tornar Primeira Bailarina. Olho-de-Peixe entra para a mesma turma e rapidamente consegue captar o olhar do Professor.
',

    ),
    146 => array(
        'nome_jp' => '十番街の休日！無邪気な王女様',
        'nome_romanji' => 'Juu-ban machi no kyuujitsu!<br>Mujakina oujo-sama',
        'nome_traduzido' => 'Férias na Rua Dez!<br>A Princesa Descontraída',
        'nome_pt' => 'O Sonho da Princesa',
		'resumo' => 'Bunny e Chibi-Usa travam amizade com uma misteriosa estrangeira, que as acompanha ao festival de verão de Juban . Sem saberem, a mulher é a desaparecida Princesa Rubina, perseguida pelo governo e por Olho de Falcão.
',

    ),
    147 => array(
        'nome_jp' => '運命のパートナー？まことの純情',
        'nome_romanji' => 'Unmei no paatonaa?<br>Makoto no junjou',
        'nome_traduzido' => 'Companheiros Destinados?<br>A Inocência da Makoto',
        'nome_pt' => 'O Baile',
		'resumo' => 'Olho-de-Tigre pretende seduzir raparigas numa festa de dança numa universidade, enquanto Makoto se apaixona imediatamente com o encantador estranho.
',

    ),
    148 => array(
        'nome_jp' => '巨悪の影！追いつめられたトリオ',
        'nome_romanji' => 'Kyoaku no kage!<br>Oitsumerareta Torio',
        'nome_traduzido' => 'Sombra do Grande Mal!<br>O Trio Encurralado',
        'nome_pt' => 'A Sombra do Mal',
		'resumo' => 'Enquanto Olho-de-Peixe toma como alvo Gonçalo, a Rainha Nehelenia aparece diante o Trio Amazonas e avisa-os que, sem o Cristal Dourado, irão brevemente voltar às suas formas animais.
',

    ),
    149 => array(
        'nome_jp' => '夢の鏡！アマゾン最後のステージ',
        'nome_romanji' => 'Yume no kagami!<br>Amazon saigo no suteeji',
        'nome_traduzido' => 'Espelho dos Sonhos!<br>Estágio Final do Trio Amazonas',
        'nome_pt' => 'O Ponto Final do Trio Amazonas',
		'resumo' => 'Olho-de-Peixe descobre que a ChibiUsa pode comunicar com Pégasus, mas recusa-se a cooperar com os planos de Zircónia. Enquanto Olho-de-Falcão persegue Bunny para obter o seu espelho dos sonhos, Zircónia decide que o Trio Amazonas viveu já o suficiente.
',

    ),
    150 => array(
        'nome_jp' => 'アマゾネス！鏡の裏から来た悪夢',
        'nome_romanji' => 'Amazonesu!<br>Kagami no ura kara kita akumu',
        'nome_traduzido' => 'As Amazonas!<br>Um Pesadelo Vindo de Trás do Espelho',
        'nome_pt' => 'O Pesadelo no Espelho',
		'resumo' => 'Zircónia envia o Quarteto Amazonas à procura do espelho dourado e o seu primeiro alvo é uma amiga de ChibiUsa, Momoko.
',

    ),
    151 => array(
        'nome_jp' => '真のパワー爆発！亜美心のしらべ',
        'nome_romanji' => 'Shin no paw bakuhatsu!<br>Ami kokoro no shirabe',
        'nome_traduzido' => 'Grande Explosão de Poder!<br>A Melodia do Coração da Ami',
        'nome_pt' => 'A Canção Romântica da Ami',
		'resumo' => 'A Ami fica obcecada com uma música que encontrou na internet e acaba por escrever uma letra para a mesma. Entretanto, VesVes do Quarteto Amazonas toma como seguinte alvo o compositor da música.
',

    ),
    152 => array(
        'nome_jp' => '炎の情熱！マーズ怒りの超必殺技',
        'nome_romanji' => 'Honoo no jounetsu!<br>Maazu ikari no chou hissawwaza',
        'nome_traduzido' => 'Chamas da Paixão!<br>Super Ataque Furioso da Mars',
        'nome_pt' => 'A Paixão do Fogo Pelos Sonhos',
		'resumo' => 'A Rita torna-se numa celebridade após aparecer num artigo de uma revista, ganhando assim uma dedicada e jovem fã de nome Nanako.
',

    ),
    153 => array(
        'nome_jp' => '恐怖の歯医者さん？パラパラの館',
        'nome_romanji' => 'Kyoufu no haisha-san?<br>Parapara no yakata',
        'nome_traduzido' => 'Dentista do Terror?<br>A Casa da PallaPalla',
        'nome_pt' => 'A Horrível Clínica Dentária',
		'resumo' => 'PallaPalla toma como seguinte alvo as crianças de Juban , espalhando cáries entre a população mais nova e estabelecendo um consultório de dentista falso.
',

    ),
    154 => array(
        'nome_jp' => '夢対決！美奈子とまこと絶交宣言',
        'nome_romanji' => 'Yume taiketsu!<br>Minako to Makoto zekkou sengen',
        'nome_traduzido' => 'Confronto de Sonhos!<br>Declaração de Amizade Absoluta de Minako e Makoto',
        'nome_pt' => 'A Navegante de Vénus Contra a Navegante de Júpiter',
		'resumo' => 'A Maria e a Joana apaixonam-se por um Educador de Infância atraente, todavia todas as tentativas por parte de Joana para fazerem com que o Professor repare nela começam prontamente a irritar Maria.
',

    ),
    155 => array(
        'nome_jp' => '恐怖を越えて！自由へのジャンプ',
        'nome_romanji' => 'Kyoufu o koete!<br>Jiyuu e no janpu',
        'nome_traduzido' => 'Além do Medo!<br>O Salto para a Liberdade',
        'nome_pt' => 'Um Salto Para a Liberdade',
		'resumo' => 'Na véspera do festival de atletismo da sua escola, um amigo de Chibiusa fica preocupado com o cavalo, obstáculo que nunca conseguiu ultrapassar. JunJun aproxima-se dele com o objectivo de obter o seu Espelho dos Sonhos oferecendo-se assim para o treinar.
',

    ),
    156 => array(
        'nome_jp' => '夢を見失わないで！真実を映す鏡',
        'nome_romanji' => 'Yume wo Miushinawanaide!<br>Shinjitsu wo Utsusu Kagami',
        'nome_traduzido' => 'Não Percas o Teu Sonho!<br>Um Espelho Que Reflete a Verdade',
        'nome_pt' => 'O Espelho da Verdade',
		'resumo' => 'Bunny e ChibiUsa tornam-se amigas de Kamoi, um jovem e esformeado artista, que é mais tarde alvo de CereCere.
',

    ),
    157 => array(
        'nome_jp' => 'ペガサスが消えた！？ゆれ動く友情',
        'nome_romanji' => 'Pegasasu ga kieta!<br>?<br>Yure ugoku yuujou',
        'nome_traduzido' => 'O Pégaso Desapareceu?<br>Amizade Tremida',
        'nome_pt' => 'A Amizade em Risco',
		'resumo' => 'Enquanto VesVes toma como alvo um amigo excentrico de ChibiUsa que tenta construir uma máquina voadora, ChibiUsa fica preocupada com o facto de que Pégasus não confia nela.
',

    ),
    158 => array(
        'nome_jp' => '天馬の秘密！夢世界を守る美少年',
        'nome_romanji' => 'Tenba no himitsu!<br>Yume sekai o mamoru bishounen',
        'nome_traduzido' => 'O Segredo do Cavalo Alado!<br>O Belo Rapaz Que Protege o Mundo dos Sonhos',
        'nome_pt' => 'O Segredo de Pégasus',
		'resumo' => 'A magia de PallaPalla faz com que Bunny e ChibiUsa troquem de idades. Enquanto ChibiUsa está deslumbrada com o seu novo corpo, fica preocupada quando descobre que não consegue contactar Pégasus.
',

    ),
    159 => array(
        'nome_jp' => 'ちびうさの小さな恋のラプソディ',
        'nome_romanji' => 'Chibi usa no chiisana koi no rapusodi',
        'nome_traduzido' => 'Pequena Rapsódia de Amor da Chibi-Usa',
        'nome_pt' => 'A Pequena Rapsódia',
		'resumo' => 'Como ChibiUsa fica cada vez mais enamorada por Helios, a Bunny e as suas amigas apercebem-se de que ela está apaixonada e ficam determinadas a descobrir a identidade da paixão de ChibiUsa.
',

    ),
    160 => array(
        'nome_jp' => '大人になる夢！アマゾネスの当惑',
        'nome_romanji' => 'Otona ni naru yume!<br>Amazonesu no touwaku',
        'nome_traduzido' => 'Sonhar Em Tornar-se Adulto!<br>A Desorientação das Amazonas',
        'nome_pt' => 'Quando Cresces',
		'resumo' => 'A Bunny e as suas amigas, juntamente com o Quarteto Amazonas, ajudam a organizar uma cerimónia de entrada na maioridade.
',

    ),
    161 => array(
        'nome_jp' => '動き出した恐怖！闇の女王の魔手',
        'nome_romanji' => 'Ugokidashita kyoufu!<br>Yami no joou no mashu',
        'nome_traduzido' => 'Terror Iminente!<br>A Mão Maléfica da Rainha da Escuridão',
        'nome_pt' => 'Nas Mãos do Inimigo',
		'resumo' => 'Para poder escapar do seu espelho, a Rainha Nehellenia faz com que aranhas apareçam em toda a cidade, lançando-a para as trevas.
',

    ),
    162 => array(
        'nome_jp' => '闇の震源地デッドムーンサーカス',
        'nome_romanji' => 'Yami no shingenchi deddomuunsaakasu',
        'nome_traduzido' => 'Circo da Lua Morta!<br>A Fonte da Escuridão',
        'nome_pt' => 'O Segredo da Tenda de Circo',
		'resumo' => 'As Guerreiras Navegantes infiltram-se no Circo da Lua Morta e são confrontadas com Zircónia.
',

    ),
    163 => array(
        'nome_jp' => '鏡の迷宮！捕えられたちびムーン',
        'nome_romanji' => 'Kagami no meikyuu!<br>Torae rareta chibi muun',
        'nome_traduzido' => 'Labirinto de Espelhos!<br>Chibi Moon Capturada',
        'nome_pt' => 'Labirinto de Espelhos',
		'resumo' => 'Depois de ser capturada pela Rainha Nehellenia, a Sailor Chibi-Moon confronta a maléfica rainha.
',

    ),
    164 => array(
        'nome_jp' => '黄金水晶出現！ネヘレニアの魔力',
        'nome_romanji' => 'Kogane suishou shutsugen!<br>Neherenia no maryoku',
        'nome_traduzido' => 'Aparece o Cristal Dourado!<br>Os Poderes Mágicos de Nehelenia',
        'nome_pt' => 'O Cristal de Ouro',
		'resumo' => 'A Rainha Nehellenia força Hélios a regressar ao seu corpo e a prescindir do Cristal de Ouro, permitindo assim à maléfica rainha libertar-se do seu espelho.
',

    ),
    165 => array(
        'nome_jp' => 'クリスタル輝く時！美しき夢の力',
        'nome_romanji' => 'Kurisutaru kagayaku toki!<br>Utsukushiki yume no chikara',
        'nome_traduzido' => 'Quando o Cristal Brilha!<br>O Poder de Sonhos Bonitos',
        'nome_pt' => 'O Poder do Cristal',
		'resumo' => 'A Rainha Nehellenia, por fim livre do seu espelho, prepara-se para usar o poder do Cristal de Ouro.
',

    ),
    166 => array(
        'nome_jp' => '夢よいつまでも！光、天に満ちて',
        'nome_romanji' => 'Yume yo itsu made mo!<br>Hikari, ten ni michite',
        'nome_traduzido' => 'Sonhos Para Sempre!<br>Encher o Céu Com Luz',
        'nome_pt' => 'Sonhos Para Sempre',
		'resumo' => 'A Rainha Nehellenia toma uma desesperada e derradeira acção depois de o Cristal de Ouro destruir o Circo da Lua Morta.
',

    ),
    167 => array(
        'nome_jp' => '悪夢花を散らす時!闇の女王復活',
        'nome_romanji' => 'Akumu hana o chirasu toki!<br>Yami no joou fukkatsu',
        'nome_traduzido' => 'Espalhar a Flor dos Pesadelos!<br>O Regresso da Rainha da Escuridão',
        'nome_pt' => 'A Nehelénia Ergue-se de Novo',
		'resumo' => 'Enquanto a Bunny e as suas amigas começam a frequentar o liceu, uma misteriosa força liberta a Rainha Nehelénia do seu espelho, encorajando-a a vingar-se delas.
',

    ),
    168 => array(
        'nome_jp' => 'サターンの目覚め!S10戦士集結',
        'nome_romanji' => 'Sataan no mezame!<br>S 10 senshi shuuketsu',
        'nome_traduzido' => 'Saturn Desperta!<br>As Dez Sailor Guerreiras Reúnem-se',
        'nome_pt' => 'Uma Nova Crise',
		'resumo' => 'Enquanto a Rainha Nehelénia continua a exercer a sua maligna influência na Terra, a Octávia, que cresce com grande rapidez, desperta de novo como Navegante de Saturno.
',

    ),
    169 => array(
        'nome_jp' => '呪いの魔鏡!悪夢にとらわれた衛',
        'nome_romanji' => 'Noroi no makyou!<br>Akumu ni torawareta Mamoru',
        'nome_traduzido' => 'Espelho Mágico Amaldiçoado!<br>Mamoru Preso Num Pesadelo',
        'nome_pt' => 'O Primeiro Ataque',
		'resumo' => 'Surgem notícias acerca de uma doença misteriosa que se tem vindo a alastrar pela populaçãoà qual Gonçalo sucumbe sendo depois raptado pela Rainha Nehelénia.
',

    ),
    170 => array(
        'nome_jp' => '運命の一夜!セーラー戦士の苦難',
        'nome_romanji' => 'Unmei no ichiya!<br>Seeraa Senshi no kunan',
        'nome_traduzido' => 'Destino Fatídico!<br>O Tormento das Sailor Guerreiras',
        'nome_pt' => 'Guerreiras em Apuros',
		'resumo' => 'A Sailor Moon entra no mundo da Rainha Nehelenia, na esperança de conseguir salvar Gonçalo e é seguida pelo resto das Guerreiras Navegantes.
',

    ),
    171 => array(
        'nome_jp' => '愛ゆえに!果てしなき魔界の戦い',
        'nome_romanji' => 'Ai yue ni!<br>Hateshinaki makai no tatakai',
        'nome_traduzido' => 'Por Amor!<br>Batalhas Sem Fim no Mundo Sombrio',
        'nome_pt' => 'Batalhas Sem Fim',
		'resumo' => 'As Guerreiras Navegantes, fechadas no mundo dos espelhos, confrontam a Rainha Nehelénia e as suas crianças-estilhaço.
',

    ),
    172 => array(
        'nome_jp' => '愛のムーンパワー!悪夢の終わる時',
        'nome_romanji' => 'Ai no muunpawaa!<br>Akumu no owaru toki',
        'nome_traduzido' => 'Poder do Amor da Lua!<br>O Fim do Pesadelo',
        'nome_pt' => 'O Fim do Pesadelo',
		'resumo' => 'A Navegante de Saturno enfrenta a Rainha Nehelénia, enquanto o Gonçalo está à beira da morte e a Sailor Chibi-Moon vê a sua existência a desvanecer.
',

    ),
    173 => array(
        'nome_jp' => '別れと出会い!運命の星々の流転',
        'nome_romanji' => 'Wakare to deai!<br>Unmei no shinshin no ruten',
        'nome_traduzido' => 'Um Adeus e Um Encontro!<br>Estrelas do Destino à Deriva',
        'nome_pt' => 'Novo Confronto',
		'resumo' => 'Enquanto as amigas de Bunny admiram o popular grupo “Os Três Luzes”, a mesma encontra Guerreiras Navegantes desconhecidas – e pelo menos algumas delas são inimigas.
',

    ),
    174 => array(
        'nome_jp' => '学園に吹く嵐!転校生はアイドル',
        'nome_romanji' => 'Gakuen ni fuku arashi!<br>Tenkousei wa aidoru',
        'nome_traduzido' => 'Tempestade Pela Escola!<br>Os Estudantes Transferidos São Ídolos',
        'nome_pt' => 'Ídolos de Campo',
		'resumo' => 'Os Três Luzes começam a estudar no mesmo liceu de Bunny e das suas amigas no qual a Sailor Iron Mouse aparece também, à procura do seu próximo alvo.
',

    ),
    175 => array(
        'nome_jp' => 'アイドルをめざせ!美奈子の野望',
        'nome_romanji' => 'Aidoru o mezase!<br>Minako no yabou',
        'nome_traduzido' => 'Tornar-se Um Ídolo!<br>A Ambição da Minako',
        'nome_pt' => 'A Ambição da Joana',
		'resumo' => 'A Joana torna-se assistente dos Três Luzes como parte do trabalho de se querer tornar num ídolo.
',

    ),
    176 => array(
        'nome_jp' => 'ファイターの正体!衝撃の超変身',
        'nome_romanji' => 'Faitaa no shoutai!<br>Shougeki no chou henshin',
        'nome_traduzido' => 'Identidade Secreta da Fighter!<br>Uma Super Transformação Chocante',
        'nome_pt' => 'As Estrelas Musicais',
		'resumo' => 'Seiya esforça-se para conseguir equilibrar o seu trabalho na escola com os pedidos de uma exigente nova realizadora.
',

    ),
    177 => array(
        'nome_jp' => '星に託す夢とロマン!大気の変身',
        'nome_romanji' => 'Hoshi ni takusu yume to roman!<br>Taiki no henshin',
        'nome_traduzido' => 'Uma Estrela de Sonhos e Desejos!<br>A Transformação do Taiki',
        'nome_pt' => 'O Cometa Wataru',
		'resumo' => 'Enquanto se preparam para a chegada de um cometa raro, Ami e Taiki discordam um com o outro acerca da necessidade de sonhos e romance nas ciências.
',

    ),
    178 => array(
        'nome_jp' => '星に託す夢とロマン!大気の変身',
        'nome_romanji' => 'Runa wa mita!<br>?<br>Aidoru yozora no sugao',
        'nome_traduzido' => 'A Descoberta da Luna!<br>A Verdadeira Face do Ídolo Yaten',
        'nome_pt' => 'O Luna Descobre Tudo',
		'resumo' => 'Luna está desparecido há uma semana. Enquanto espalham cartazes pela cidade, Bunny e as amigas descobrem Luna no colo de Yaten, em pleno directo televisivo.
',

    ),
    179 => array(
        'nome_jp' => '敵?味方?スターライツとS戦士',
        'nome_romanji' => 'Teki?<br>Mikata?<br>Sutaaraitsu to S senshi',
        'nome_traduzido' => 'Amigas ou Inimigas?<br>Starlights e as Sailor Guerreiras',
        'nome_pt' => 'O Génio da Cozinha',
		'resumo' => 'O Taiki convida a Maria a aparecer num programa de culinária com ele, todavia a Bunny faz-se de convidada, com efeitos desastrosos.
',

    ),
    180 => array(
        'nome_jp' => '呼び合う星の輝き!はるか達参戦',
        'nome_romanji' => 'Yobi au hoshi no kagayaki!<br>Haruka-tachi sansen',
        'nome_traduzido' => 'Apelo das Estrelas Brilhantes!<br>Entram Haruka e Michiru',
        'nome_pt' => 'Intrusas ou Amigas?',
		'resumo' => 'Depois de um concerto conjunto de Mariana e os Três Luzes, as Navegantes de Úrano e Neptuno confrontam as Sailor Starlights, declarando que é impossível tornarem-se aliadas.
',

    ),
    181 => array(
        'nome_jp' => 'セイヤとうさぎのドキドキデート',
        'nome_romanji' => 'Seiya to Usagi no dokidokideeto',
        'nome_traduzido' => 'Encontro Palpitante de Seiya e Usagi',
        'nome_pt' => 'O Fim da Sailor Iron Mouse',
		'resumo' => 'O Seiya convida a Bunny para sair com ele e Bunny pergunta-se porque será. Enquanto estão juntos, são confrontados pela Sailor Iron Mouse, que tenta roubar a Semente de Estrela de Seiya.
',
    ),
    182 => array(
        'nome_jp' => '宇宙からの侵略!セイレーン飛来',
        'nome_romanji' => 'Uchuu kara no shinryaku!<br>Seireen hirai',
        'nome_traduzido' => 'Invasores do Espaço Exterior!<br>A Chegada de Siren',
        'nome_pt' => 'Um Novo Membro',
		'resumo' => 'Uma estranha e pequena menina aparece e faz com que a mãe de Bunny acredite que ela é a irmã mais nova de Bunny. Entretanto, as duas novas Sailor Animamates fazem a sua primeira aparição.
',

    ),
    183 => array(
        'nome_jp' => '死霊の叫び!?恐怖キャンプの怪人',
        'nome_romanji' => 'Shiryou no sakebi!<br>?<br>Kyoufu kyanpu no kaijin',
        'nome_traduzido' => 'Mortos Que Gritam!<br>Terror do Monstro do Acampamento',
        'nome_pt' => 'Pânico numa Estância de Verão',
		'resumo' => 'Durante um acampamento, as meninas descobrem que o primo de Rita foi transformado em Phage e que tem vindo a destruir o parque de campismo.
',

    ),
    184 => array(
        'nome_jp' => 'ふたりきりの夜!うさぎのピンチ',
        'nome_romanji' => 'Futari kiri no yoru!<br>Usagi no pinchi',
        'nome_traduzido' => 'Uma Noite Sozinha Juntos!<br>Usagi Em Perigo',
        'nome_pt' => 'Uma Noite de Confusão',
		'resumo' => 'O Seiya visita Bunny enquanto a mesma está sozinha em casa, sendo que prontamente surgem novas visitas – incluindo a Sailor Lead Crow e a Sailor Aluminum Seiren.
',

    ),
    185 => array(
        'nome_jp' => '大気絶唱!信じる心を歌にこめて',
        'nome_romanji' => 'Taiki zesshou!<br>Shinjiru kokoro o uta ni komete',
        'nome_traduzido' => 'A canção do Taiki! Colocando o coração cheio de fé na música',
        'nome_pt' => 'Aqueles Que Acreditam',
		'resumo' => 'Taiki tem dúvidas acerca da sua missão até conhecer uma menina chamada Misa que o ajuda a acreditar em si próprio.
',

    ),
    186 => array(
        'nome_jp' => 'ちびちびの謎!おさわがせ大追跡',
        'nome_romanji' => 'Chibichibi no nazo!<br>O sawagase dai tsuiseki',
        'nome_traduzido' => 'O Mistério da Chibi Chibi!<br>Grande Perseguição Barulhenta',
        'nome_pt' => 'A Terra do Doce',
		'resumo' => 'ChibiChibi começa a desaparecer da vista de Bunny e a aparecer de novo com doces, por isso Bunny segue-a na esperança de encontrar um mundo de doces.
',

    ),
    187 => array(
        'nome_jp' => '輝く星のパワー!ちびちびの変身',
        'nome_romanji' => 'Kagayaku hoshi no pawaa!<br>Chibichibi no henshin',
        'nome_traduzido' => 'Poder Brilhante de Uma Estrela!<br>A Transformação da Chibi-Chibi',
        'nome_pt' => 'A Primeira Manobra da Chibi Chibi',
		'resumo' => 'A Sailor Aluminum Seiren ataca a Sailor Moon na tentativa de lhe tirar a sua semente de estrela, mas a mesma é salva por uma nova Guerreira Navegante – Sailor ChibichibiMoon.
',

    ),
    188 => array(
        'nome_jp' => '恐怖への招待!うさぎの夜間飛行',
        'nome_romanji' => 'Kyoufu e no shoutai!<br>Usagi no yakan hikou',
        'nome_traduzido' => 'Convite Para o Terror!<br>O Voo Noturno da Usagi',
        'nome_pt' => 'Convite ao Terror',
		'resumo' => 'A Sailor Aluminum Seiren oferece à Bunny bilhetes para um voo especial num avião com os Três Luzes, com o intento de a encurralar e lhe roubar a sua Semente de Estrela.
',

    ),
    189 => array(
        'nome_jp' => '使命と友情の間!S戦士達の対立',
        'nome_romanji' => 'Shimei to yuujou no ma!<br>S senshi-tachi no tairitsu',
        'nome_traduzido' => 'Dever ou Amizade!<br>Conflito Entre as Sailor Guerreiras',
        'nome_pt' => 'A Missão ou a Amizade?',
		'resumo' => 'As Sailor Starlights e as Guerreiras Navegantes do Sistema Solar, agora que sabem as suas identidades, não confiam umas nas outras – ainda que Seiya e Bunny estejam convencidos que conseguem trabalhar juntos.
',

    ),
    190 => array(
        'nome_jp' => '明かされた真実!セイヤ達の過去',
        'nome_romanji' => 'Akasaretashinjitsu!<br>Seiya-tachi no kako',
        'nome_traduzido' => 'Verdade Revelada!<br>O Passado de Seiya, Yaten e Taiki',
        'nome_pt' => 'O Passado dos Três Luzes',
		'resumo' => 'Mesmo sabendo que os seus amigos não permitem que se vejam, Seiya e Bunny estão convencidos que podem ser aliados e assim Seiya consegue encontrar uma maneira de contar a Bunny o passado das Sailor Starlights.
',

    ),
    191 => array(
        'nome_jp' => '光の蝶が舞う時!新しい波の予感',
        'nome_romanji' => 'Hikari no chou ga mau toki!<br>Atarashii nami no yokan',
        'nome_traduzido' => 'Quando Voam Borboletas de Luz!<br>Sensação de Um Novo Capítulo',
        'nome_pt' => 'Quando a Borboleta de Luzes Voa',
		'resumo' => 'As meninas concorrem num concurso de jogos onde o Taiki aparece e Ami fica determinada em ganhar para que consiga ter uma hipótese de falar com ele.
',

    ),
    192 => array(
        'nome_jp' => '夢一直線!アイドル美奈子の誕生',
        'nome_romanji' => 'Yume itchokusen!<br>Aidoru Minako no tanjou',
        'nome_traduzido' => 'Segue o Teu Sonho!<br>O Nascimento da Ídolo Minako',
        'nome_pt' => 'Luta Pelo Teu Sonho',
		'resumo' => 'A Joana entra numa competição de ídolos onde Yaten faz parte do júri – assim como Suzu Nyanko.
',

    ),
    193 => array(
        'nome_jp' => 'うばわれた銀水晶!火球皇女出現',
        'nome_romanji' => 'Ubawa reta ginsuishou!<br>Kakyuu koujo shutsugen',
        'nome_traduzido' => 'Cristal Prateado Roubado!<br>Aparece a Princesa Kakyu',
        'nome_pt' => 'A Princesa Iluminada Aparece',
		'resumo' => 'A Sailor Lead Crow toma como seguinte alvo a Sailor Moon para obter a sua semente de estrela, enquanto as Sailor Starlights começam a perseguir ChibiChibi com o pensamento de que ela sabe onde a sua princesa está.
',

    ),
    194 => array(
        'nome_jp' => '銀河の聖戦 セーラーウォーズ伝説',
        'nome_romanji' => 'Ginga no seisen seeraau~ouzu densetsu',
        'nome_traduzido' => 'Cruzada Pela Galáxia!<br>Lenda das Guerras Sailor',
        'nome_pt' => 'A Lenda das Guerreiras Navegantes',
		'resumo' => 'Ainda que as amigas de Bunny estejam determinadas em a proteger, a Sailor Tin Nyakno consegue atacá-la sendo depois salva por Seiya.
',

    ),
    195 => array(
        'nome_jp' => '火球皇女消滅!ギャラクシア降臨',
        'nome_romanji' => 'Kakyuu koujo shoumetsu!<br>Gyarakushia kourin',
        'nome_traduzido' => 'Morte da Princesa Kakyu!<br>A Chegada de Galaxia',
        'nome_pt' => 'A Galáxia Chega à Terra',
		'resumo' => 'Os Três Luzes anunciam que se vão separar e que assim irão realizar um último concerto, contudo durante o concerto a Sailor Galáxia aparece e ataca as Guerreiras Navegantes.
',

    ),
    196 => array(
        'nome_jp' => '銀河滅びる時!S戦士最後の戦い',
        'nome_romanji' => 'Ginga horobiru toki!<br>S senshi saigonotatakai',
        'nome_traduzido' => 'Chegou a Hora de Destruir a Galáxia!<br>Batalha Final das Sailor Guerreiras',
        'nome_pt' => 'Batalha Final Para as Guerreiras Navegantes',
		'resumo' => 'A batalha final contra a Sailor Galáxia começa e as Guerreiras Navegantes dirigem-se para a Estação de Televisão Ginga para a confrontar.
',

    ),
    197 => array(
        'nome_jp' => '銀河の支配者!<br>ギャラクシアの脅威',
        'nome_romanji' => 'Ginga no shihai-sha gyarakushia no kyoui',
        'nome_traduzido' => 'Governante da Galáxia!<br>A Ameaça de Galaxia',
        'nome_pt' => 'A Ameaça da Galáxia',
		'resumo' => 'As Guerreiras da Parte Exterior do Sistema Solar tentam combater com Galáxia mas os seus ataques pouco a afectam – e quando a mesma lhes oferece a hipótese de se juntarem a ela, as Navegantes de Úrano e Neptuno aceitam.
',

    ),
    198 => array(
        'nome_jp' => '消えゆく星々!ウラヌス達の最期',
        'nome_romanji' => 'Kie yuku shinshin!<br>Uranusu-tachi no saigo',
        'nome_traduzido' => 'Estrelas Moribundas!<br>O Último Esforço de Uranus e Neptune',
        'nome_pt' => 'O Desaparecimento das Estrelasː o Fim da Úrano e das Outras',
		'resumo' => 'As Navegantes de Úrano e Neptuno, tendo juntado forças com Sailor Galáxia, tentam usar os seus novos poderes contra a mesma- todavia, falham.
',

    ),
    199 => array(
        'nome_jp' => '希望の光!銀河をかけた最終決戦',
        'nome_romanji' => 'Kibou no hikari!<br>Ginga o kaketa saishuu kessen',
        'nome_traduzido' => 'Luz da Esperança!<br>Batalha Final pela Galáxia',
        'nome_pt' => 'A Última Batalha da Galáxia',
		'resumo' => 'As Sailor Starlights lutam uma batalha perdida contra Sailor Galaxia e, no momento em que tudo parece perdido, a Sailor ChibiChibiMoon revela-se como sendo a Luz da Esperança.
',

    ),
    200 => array(
        'nome_jp' => 'うさぎの愛!月光銀河を照らす',
        'nome_romanji' => 'Usagi no ai!<br>Gekkou ginga o terasu',
        'nome_traduzido' => 'O Amor da Usagi!<br>O Luar Ilumina a Galáxia',
        'nome_pt' => 'O Amor da Bunnyː o Luar Que Brilha na Galáxia',
		'resumo' => 'A batalha final finalmente termina, quando a Sailor Moon liberta a Sailor Galaxia da influência do Caos. O último episódio da série
',

    ),
);

// INICIO DAS FUNCOES
//Não mexer!
function epi_shortcode($atts, $content = null, $tags = '') {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'num' => 1, // Default value for 'num' attribute
        'campo' => '', // Default value for 'campo' attribute
    ), $atts, 'epi');

    // Extract attributes
    $num = intval($atts['num']); // Convert 'num' attribute to integer
    $campo = sanitize_text_field($atts['campo']); // Sanitize 'campo' attribute

    // Check if the arrays are defined
    if (isset($GLOBALS['episode_data'])) {
        $episode_data = $GLOBALS['episode_data'];

        // Check if the episode number exists in the array
        if (array_key_exists($num, $episode_data)) {
            // Check if the campo exists in the episode data
            if (!empty($campo) && array_key_exists($campo, $episode_data[$num])) {
                return $episode_data[$num][$campo]; // Return the value of the specified campo
            } elseif (!empty($campo) && $campo == 'link_hd') {
                // If the campo is 'link_hd', return the corresponding external link from $link_hd array
                $link_hd = isset($GLOBALS['link_hd']) ? $GLOBALS['link_hd'] : array();
                return isset($link_hd[$num]) ? $link_hd[$num] : '';
            }
        }
    }

    return 'Erro';
}

add_shortcode('epi', 'epi_shortcode');


//RMVBS

function register_shortcodes($start, $end, $season, $prefix, $exceptions = []) {
    // Mapping of specific episode exceptions to their URLs
    $episodes_v2_mapping = [
        178 => 'http://sm-portugal.com/multimedia/episodios/Sailor%20Moon%20integral%20-%20baixa%20qualidade%202007%202008/S05%20-%20SailorStars/Sailor%20Moon%20-%20Epis%c3%b3dio%20178v2%20%5bRMVB%202007-2008%5d.rmvb',
    ];

    for ($i = $start; $i <= $end; $i++) {
        if (in_array($i, $exceptions) && isset($episodes_v2_mapping[$i])) {
            $url = $episodes_v2_mapping[$i];
        } else {
            $url = "http://sm-portugal.com/multimedia/episodios/Sailor%20Moon%20integral%20-%20baixa%20qualidade%202007%202008/$season/Sailor%20Moon%20-%20Epis%c3%b3dio%20" . sprintf('%03d', $i) . "%20%5bRMVB%202007-2008%5d.rmvb";
        }

        // Register shortcode with prefix
        $shortcode_name = $prefix . $i;
        add_shortcode($shortcode_name, function ($atts) use ($url) {
            return $url;
        });

        // Register additional shortcode
        add_shortcode('link_rmvb' . $i, function ($atts) use ($url) {
            return $url;
        });
    }
}

// Register shortcodes for specific ranges and exceptions
register_shortcodes(167, 200, 'S05%20-%20SailorStars', 'rmvb', [178]);
?>