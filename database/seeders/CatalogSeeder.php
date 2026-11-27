<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $slug => $name) {
            $category = Category::query()->firstOrNew(['slug' => $slug]);
            $category->name = $name;
            $category->slug = $slug;
            $category->save();
        }

        foreach ($this->books() as $book) {
            $category = Category::query()->where('slug', $book['category'])->firstOrFail();
            $product = Product::query()->firstOrNew(['slug' => $book['slug']]);
            $product->category_id = $category->id;
            $product->title = $book['title'];
            $product->slug = $book['slug'];
            $product->author = $book['author'];
            $product->description = $book['description'];
            $product->content = $book['content'];
            $product->price_cents = $book['price_cents'];
            $product->is_featured = $book['featured'];
            $product->is_published = $book['published'];
            $product->cover_path = $book['cover']
                ? $this->writeCover($book['slug'], $book['title'], $book['color'])
                : null;
            $product->save();
        }
    }

    /**
     * @return array<string, string>
     */
    private function categories(): array
    {
        return [
            'contos' => 'Contos',
            'ensaios' => 'Ensaios',
            'cadernos' => 'Cadernos',
        ];
    }

    private function writeCover(string $slug, string $title, string $color): string
    {
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) !== 1) {
            throw new InvalidArgumentException('Cor de capa inválida.');
        }

        $safeTitle = htmlspecialchars($title, ENT_XML1 | ENT_QUOTES);
        $text = '';
        $y = 520;

        foreach (explode("\n", wordwrap($title, 22, "\n", true)) as $line) {
            $safe = htmlspecialchars($line, ENT_XML1 | ENT_QUOTES);
            $text .= '<text x="72" y="'.$y.'" fill="#F6F1E7" font-family="Georgia, serif" font-size="48">'.$safe.'</text>';
            $y += 64;
        }

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="800" height="1200" viewBox="0 0 800 1200">
            <title>{$safeTitle}</title>
            <rect width="800" height="1200" fill="{$color}"/>
            {$text}
        </svg>
        SVG;

        $path = 'covers/'.$slug.'.svg';
        Storage::disk('public')->put($path, trim($svg));

        return $path;
    }

    /**
     * @return list<array{
     *     slug: string,
     *     category: string,
     *     title: string,
     *     author: string,
     *     description: string,
     *     content: string,
     *     price_cents: int,
     *     featured: bool,
     *     published: bool,
     *     cover: bool,
     *     color: string
     * }>
     */
    private function books(): array
    {
        return [
            [
                'slug' => 'a-janela-do-terceiro-andar',
                'category' => 'contos',
                'title' => 'A janela do terceiro andar',
                'author' => 'Lena Vilar',
                'description' => 'Uma inquilina inventa a vida da praça até perceber que a praça também a espera.',
                'price_cents' => 3790,
                'featured' => true,
                'published' => true,
                'cover' => true,
                'color' => '#6B2E32',
                'content' => <<<'TEXT'
A janela do terceiro andar não tinha cortina. Lena chegou com uma mala e um contrato de três meses, e a primeira coisa que fez foi arrastar a cadeira até o vidro. A praça embaixo era pequena demais para o nome: quatro bancos, um chafariz seco e uma árvore que perdia folhas mesmo em dia de sol.

De manhã passava o homem do carrinho de pão. Ele cumprimentava todo mundo e ninguém pelo nome, como se a praça fosse um corredor de prédio. Lena inventou que ele conhecia a receita de cada cliente. Para a senhora do casaco vermelho, pão doce. Para o menino da mochila rasgada, um salgado que ele dividia com alguém que Lena ainda não tinha visto.

Na segunda semana, a senhora do casaco parou de aparecer. O homem do pão olhou para a janela, não para a praça, e levantou o saco de papel como quem pergunta. Lena demorou a entender que a pergunta era para ela. Desceu sem casaco, comprou o pão doce e subiu de novo. O pão estava frio. Mesmo assim, no dia seguinte, ela desceu antes de ele chegar.

O contrato acabou numa terça. Lena deixou a cadeira onde estava. No último olhar, o chafariz continuava seco, e o homem do pão já tinha separado dois sacos: um para a praça, outro para a janela do terceiro andar. Ela não soube se aquilo era um adeus ou um hábito novo. Guardou a chave em cima da geladeira, como se três meses pudessem ser dobrados no meio.

Na rua, o menino da mochila acenou sem saber o nome dela. Lena acenou de volta. A praça, pequena como era, coube inteira naquele gesto. O restante da mudança cabia numa mala, e a mala já estava fechada.
TEXT,
            ],
            [
                'slug' => 'o-mapa-que-cabia-no-bolso',
                'category' => 'contos',
                'title' => 'O mapa que cabia no bolso',
                'author' => 'Tomás Ribeiro',
                'description' => 'Um mapa de ruas que não existem ensina um caminho de volta pela casa.',
                'price_cents' => 2500,
                'featured' => true,
                'published' => true,
                'cover' => false,
                'color' => '',
                'content' => <<<'TEXT'
Tomás achou o mapa no bolso de um casaco que não era seu. A etiqueta interna dizia "provador", e o papel dobrado tinha ruas com nomes de frutas: Rua da Amora, Largo do Figo, Beco da Carambola. Nenhuma delas existia no celular.

Ele saiu mesmo assim. A cidade de verdade teimava em parecer com o desenho. Uma padaria ocupava o lugar onde o mapa prometia uma ponte. Um ponto de ônibus fazia a curva que, no papel, era um rio. Tomás anotou as diferenças na margem, com letra pequena, até o mapa ficar mais verdade do que invenção.

No fim da tarde, uma mulher perguntou se ele estava perdido. Tomás mostrou o papel. Ela riu e disse que o avô desenhava mapas assim para não esquecer o caminho de volta quando a memória apertava. Não era um mapa de lugar. Era um mapa de jeito.

Tomás devolveu o casaco no provador e ficou com a cópia que tinha feito no verso de um recibo. Em casa, pregou o recibo na geladeira. A Rua da Amora, decidiu, ficava entre a pia e a janela, que era por onde a luz entrava quando ele esquecia de acender a lâmpada.

Antes de dormir, dobrou o recibo de novo até caber no bolso. No dia seguinte não precisou dele. Lembrou sozinho onde tinha deixado as chaves, o que, para Tomás, já contava como geografia.
TEXT,
            ],
            [
                'slug' => 'chuva-na-escada-de-ferro',
                'category' => 'contos',
                'title' => 'Chuva na escada de ferro',
                'author' => 'Clara Mendonça',
                'description' => 'Dois vizinhos dividem uma goteira e saem de lá com um horário, não com um conserto.',
                'price_cents' => 1890,
                'featured' => false,
                'published' => true,
                'cover' => false,
                'color' => '',
                'content' => <<<'TEXT'
A escada de ferro dos fundos pingava em três pontos, sempre os mesmos. Clara subiu com uma bacia, porque a goteira do apartamento de cima escolhia o corredor dela. No patamar, Duda já estava lá, com um pano e a paciência de quem perde essa disputa toda quinta-feira.

Os dois não se conheciam de nome, só de barulho: ela ouvia a cadeira dele arrastar, ele ouvia a chaleira dela. A chuva aumentou e a conversa ficou pequena. Falaram do preço do gás, de um gato que não era de ninguém e de um elevador que parava entre o segundo e o terceiro como se pensasse.

Nenhum dos dois propôs um conserto. A escada não pedia solução. Pedia companhia enquanto a água decidia o caminho. Quando a chuva afrouxou, Duda torceu o pano e Clara esvaziou a bacia no ralo. Combinaram, sem marcar hora, que na próxima quinta a bacia e o pano se encontrariam de novo.

Clara desceu achando que isso, para uma escada de ferro, já era um endereço. Em casa, deixou a bacia perto da porta, virada para secar. Não era pressa. Era o jeito de dizer que a quinta-feira seguinte já tinha lugar marcado, mesmo que a chuva faltasse.
TEXT,
            ],
            [
                'slug' => 'o-relogio-da-padaria',
                'category' => 'contos',
                'title' => 'O relógio da padaria',
                'author' => 'Ivo Lacerda',
                'description' => 'Um relógio quatro minutos adiantado organiza o bairro, e ninguém quer consertá-lo.',
                'price_cents' => 4100,
                'featured' => false,
                'published' => false,
                'cover' => false,
                'color' => '',
                'content' => <<<'TEXT'
O relógio da padaria adiantava quatro minutos desde 1998, e ninguém tinha coragem de corrigir. O padeiro dizia que o atraso do mundo cabia naqueles quatro minutos, e que quem chegasse na hora pelo relógio dele ainda encontrava o pão quente.

Ivo morava em cima e descia descalço para acertar o próprio dia. Entregava as encomendas quatro minutos antes do combinado e pedia desculpa, como se a pontualidade fosse um defeito. Os clientes riam e pagavam. O bairro inteiro passou a sair de casa um pouco mais cedo, sem perceber que obedecia a um relógio teimoso.

Uma tarde, um técnico ofereceu trocar o mecanismo de graça. O padeiro recusou. Ivo, que ouviu da escada, desceu e pediu só uma coisa: que, se um dia o relógio fosse consertado, alguém avisasse o bairro com a mesma antecedência de quatro minutos.

O técnico foi embora sem entender. Ivo voltou para cima e deixou o próprio relógio de pulso adiantado também. Ainda não contou isso a ninguém. Por enquanto, a padaria continua certa do seu jeito errado, e o pão segue quente para quem aceita viver um pouco antes da hora.
TEXT,
            ],
            [
                'slug' => 'como-guardar-um-dia-comum',
                'category' => 'ensaios',
                'title' => 'Como guardar um dia comum',
                'author' => 'Nara Pimentel',
                'description' => 'Um dia sem legenda cabe numa gaveta, não numa lista de metas.',
                'price_cents' => 4200,
                'featured' => true,
                'published' => true,
                'cover' => true,
                'color' => '#2C3A2F',
                'content' => <<<'TEXT'
Um dia comum não pede fotografia. Pede uma gaveta. Nara escreveu isso numa folha solta e depois passou a manhã inteira sem produzir nada que pudesse ser mostrado, o que lhe pareceu um bom começo.

Guardar um dia desses é anotar o que não serviria para uma legenda. A xícara lascada que só ela usa. O ônibus que chegou cheio e mesmo assim parou. A frase que ficou pela metade porque o feirante perguntou se ela queria coentro. Nada disso rende um relato. Tudo isso, somado, é a vida que a pressa chama de intervalo.

Há quem transforme o comum em meta: contar copos de água, medir o passo, publicar o prato. Nara experimentou por uma semana e terminou com fome e uma lista. Voltou ao método antigo. No caderno, três linhas. Se o dia couber em três linhas, ele aconteceu. Se não couber, também aconteceu, e o caderno não é um tribunal.

Ela recomenda, a quem lhe pede um método, uma gaveta e não um aplicativo. A gaveta não manda notificação. De vez em quando, Nara abre a dela e encontra um ingresso antigo, um botão e a folha em que escreveu a primeira frase. O dia daquela folha ela não lembra inteiro. Lembra que foi comum. Isso tem sido suficiente.
TEXT,
            ],
            [
                'slug' => 'o-silencio-entre-duas-estacoes',
                'category' => 'ensaios',
                'title' => 'O silêncio entre duas estações',
                'author' => 'Heitor Dantas',
                'description' => 'O minuto sem paisagem do metrô mostra o que nenhum mapa desenha.',
                'price_cents' => 5600,
                'featured' => false,
                'published' => true,
                'cover' => false,
                'color' => '',
                'content' => <<<'TEXT'
Entre uma estação e outra, o metrô apaga a paisagem e deixa o passageiro com o próprio reflexo. Heitor passou a cronometrar esse intervalo sem relógio, contando as juntas do trilho. Deu quarenta e duas, na linha que pega de manhã. Deu trinta e oito, na volta, como se a cidade encolhesse depois do expediente.

Nesse trecho não há anúncio útil. Há tosse, fone vazando uma batida e a decisão pequena de ceder ou não o lugar. Heitor se interessa por essa decisão porque ela não aparece em nenhum mapa. O mapa mostra estações. O silêncio mostra gente.

Ele anota, num bloco que não mostra a ninguém, o que as pessoas fazem com as mãos quando não há o que olhar. Seguram a alça. Dobram o bilhete que já não serve. Conferem a bolsa sem abrir. São gestos de quem atravessa um túnel e prefere não transformar isso em pensamento.

O ensaio não conclui que o silêncio melhora alguém. Conclui que ele existe, dura menos de um minuto e acaba com a luz da estação seguinte. Heitor desce. A contagem zera. Amanhã as juntas do trilho estarão no mesmo lugar, o que é uma forma discreta de promessa.
TEXT,
            ],
            [
                'slug' => 'notas-sobre-mesas-vazias',
                'category' => 'ensaios',
                'title' => 'Notas sobre mesas vazias',
                'author' => 'Marina Quaresma',
                'description' => 'Depois que o café fecha, a mesa guarda o que a conversa não levou.',
                'price_cents' => 990,
                'featured' => false,
                'published' => true,
                'cover' => false,
                'color' => '',
                'content' => <<<'TEXT'
Depois que o café fecha, as mesas ficam mais honestas. Marina ajuda a recolher os copos e observa o que sobra: um sachê de açúcar aberto, um guardanapo com um número de telefone sem nome, a marca redonda de um copo que alguém girou enquanto esperava.

Uma mesa vazia não é uma ausência. É o intervalo entre dois usos. De manhã, alguém vai sentar ali e não saber que, horas antes, outra pessoa quase disse uma frase e desistiu. Marina não romantiza a desistência. Só acha que a mesa guarda melhor o quase do que a conversa guardaria.

Ela escreve estas notas de pé, no balcão, enquanto a cadeira ainda está quente. Três observações por noite. Mais do que isso vira crônica, e a crônica pede um final. A mesa não tem final. Tem um pano úmido e a luz que o gerente apaga por último.

Na última nota da semana, Marina descreve a mesa da janela sem ninguém e sem louça. Parecia, diz ela, um convite que não precisava de convidado. No dia seguinte, a primeira cliente sentou exatamente ali e pediu o de sempre. A mesa aceitou, como aceita tudo o que chega com fome.
TEXT,
            ],
            [
                'slug' => 'caderno-de-bordo-sem-porto',
                'category' => 'cadernos',
                'title' => 'Caderno de bordo sem porto',
                'author' => 'Paulo Ventura',
                'description' => 'Um caderno de quem viaja de ônibus dentro da mesma cidade, sem eleger um bairro.',
                'price_cents' => 1500,
                'featured' => true,
                'published' => true,
                'cover' => false,
                'color' => '',
                'content' => <<<'TEXT'
Paulo decidiu viajar sem sair da cidade. Comprou um caderno de capa azul e um bilhete de ônibus que dava para circular o dia inteiro. A regra era simples: descer em um ponto que não conhecesse e anotar uma coisa que não caberia num guia.

No primeiro dia, anotou o nome de um cachorro pintado na parede de um mercadinho. No segundo, o cheiro de sabão que saía de uma lavanderia sem placa. No terceiro, uma fila em que as pessoas conversavam como se fossem parentes e se despediam como estranhos. Nenhum desses achados tinha endereço estável. Por isso serviam.

O caderno não tem porto porque Paulo recusou escolher um bairro favorito. Favorito, escreveu, é uma forma de parar. Ele queria a continuidade do banco de trás, a janela suja e o motorista que avisa a próxima parada com uma palavra só.

Na página vinte, há um mapa feito de setas e de horários de ônibus. Não leva a lugar nenhum que o leitor reconheça. Leva de volta ao ponto em que Paulo sobe outra vez, com o mesmo caderno e um bilhete novo. A viagem, ele insiste na última linha, começa quando a porta fecha e alguém ainda está procurando o cartão.
TEXT,
            ],
            [
                'slug' => 'receitas-para-quem-mora-sozinho',
                'category' => 'cadernos',
                'title' => 'Receitas para quem mora sozinho',
                'author' => 'Elisa Carmo',
                'description' => 'Anotações de quem cozinha para um e trata o horário como parte do prato.',
                'price_cents' => 7300,
                'featured' => false,
                'published' => true,
                'cover' => false,
                'color' => '',
                'content' => <<<'TEXT'
Elisa não coleciona receitas. Coleciona jeitos de comer quando não há para quem servir. O arroz, por exemplo, ela faz numa panela pequena e escuta o ponto pela tampa, porque medir a água com copo a deixa com a sensação de estar cozinhando para uma família que não chegou.

Há uma anotação sobre ovo: frito, com a borda crocante, comido em pé na cozinha enquanto a torrada esfria. Não é um prato. É um horário. Elisa descobriu que morar sozinha pede horários mais do que cardápios. Quem come na hora certa não transforma a noite numa geladeira aberta.

Outra página ensina a dividir um molho em dois potes. Um para hoje, outro para não decidir amanhã. Ela chama isso de gentileza com a própria fome futura. Não há técnica secreta. Há tampa que fecha bem e uma etiqueta com o dia da semana, escrita à caneta, torta de propósito.

No fim do caderno, Elisa deixa uma receita sem ingredientes: sentar, pôr o prato na mesa e não olhar o celular até a última garfada. Ela avisa que essa é a mais difícil. As outras cabem numa panela. Essa pede uma cadeira e um pouco de coragem ordinária.
TEXT,
            ],
        ];
    }
}
