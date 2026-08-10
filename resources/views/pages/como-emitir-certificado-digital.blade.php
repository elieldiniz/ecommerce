<x-layout title="Como Emitir Certificado Digital Online | Passo a Passo | Digital Lock">
    <x-slot:meta_description>Veja o passo a passo completo para emitir seu Certificado Digital pela internet, por videoconferência, do pagamento à instalação.</x-slot:meta_description>

    <x-breadcrumb :items="[
        ['label' => 'Como emitir'],
    ]" />

    <section class="w-full bg-white px-6 py-16 md:px-10 md:py-24">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-xl">
                <h1 class="mb-3 font-heading text-3xl leading-tight font-bold text-ink md:text-4xl">Como emitir seu Certificado Digital</h1>
                <p class="mb-5.5 font-sans text-base leading-relaxed text-muted">Todo o processo é feito pela internet, por videoconferência, no dia e no horário que você escolher. Veja como funciona do começo ao fim.</p>
                <a href="/certificado-digital/" class="inline-block rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white">Ver certificados</a>
            </div>
        </div>
    </section>

    {{-- O processo em quatro etapas --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-5 font-heading text-2xl font-bold text-ink">O processo em quatro etapas</h2>
            <x-passo-a-passo card="white" />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Do pagamento à instalação, o tempo depende principalmente do horário que você escolher para a validação.</p>
        </div>
    </section>

    {{-- Etapa 1: elegibilidade --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Etapa 1</x-eyebrow>
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Confira se você se enquadra</h2>
            <x-elegibilidade-videoconferencia />
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Se você não se enquadra em nenhuma das duas, fale com a nossa equipe antes de comprar.</p>
        </div>
    </section>

    {{-- Etapa 2: documentos --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Etapa 2</x-eyebrow>
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Separe os documentos</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Para e-CPF</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Documento de identidade oficial com foto: registro de identidade, passaporte, título de eleitor com foto ou CNH. Estrangeiro domiciliado apresenta CNE; não domiciliado, passaporte. CPF, se não constar.</p>
                </div>
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">Para e-CNPJ</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Ato constitutivo registrado, documentos de eleição dos administradores quando aplicável, cartão CNPJ e identidade do representante legal.</p>
                </div>
            </div>
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Original físico ou digital. Documentos digitais são verificados em aplicações oficiais. Havendo divergência nos dados, a emissão é suspensa até a regularização.</p>
        </div>
    </section>

    {{-- Etapa 3: agendamento --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Etapa 3</x-eyebrow>
            <h2 class="mb-4 font-heading text-2xl font-bold text-ink">Agende sua videoconferência</h2>
            <p class="mb-3 max-w-2xl font-sans text-sm leading-relaxed text-muted">Depois da confirmação do pagamento, você recebe um e-mail com o link de agendamento.</p>
            <p class="mb-2.5 font-sans text-sm leading-relaxed text-muted">Tenha em mãos:</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-2 font-sans text-[13px] leading-relaxed text-muted md:grid-cols-2">
                <div>Número do pedido</div>
                <div>Nome completo do titular</div>
                <div>CPF do titular</div>
                <div>Data de nascimento</div>
                <div>Razão social e CNPJ, no caso de pessoa jurídica</div>
                <div>Telefone</div>
                <div>E-mail</div>
            </div>
        </div>
    </section>

    {{-- Etapa 4: prepare o ambiente --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Etapa 4</x-eyebrow>
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Prepare o ambiente da chamada</h2>
            <div class="grid grid-cols-1 gap-x-6 gap-y-2 font-sans text-[13px] leading-relaxed text-muted md:grid-cols-2">
                <div>Conexão estável, de preferência rede fixa</div>
                <div>Ambiente iluminado, luz de frente</div>
                <div>Câmera e microfone testados antes</div>
                <div>Rosto descoberto, sem boné ou óculos escuros</div>
                <div>Silêncio, sem barulho de fundo</div>
                <div>Documentos originais em mãos</div>
            </div>
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">A chamada é gravada e registrada em dossiê eletrônico, conforme os normativos da LGPD.</p>
        </div>
    </section>

    {{-- Etapa 5: o que acontece na chamada --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Etapa 5</x-eyebrow>
            <h2 class="mb-4 font-heading text-2xl font-bold text-ink">O que acontece na chamada</h2>
            <p class="mb-2.5 max-w-2xl font-sans text-sm leading-relaxed text-muted">O atendente confere sua identidade, você apresenta os documentos à câmera e os dados do certificado são confirmados. É uma conferência, não uma entrevista.</p>
            <p class="max-w-2xl font-sans text-sm leading-relaxed text-muted">A validação é feita pelo próprio titular, ou pelo responsável legal no caso de pessoa jurídica. Terceiro não valida no seu lugar, salvo por procuração pública com poder específico e validade de 90 dias, ou por curatela.</p>
        </div>
    </section>

    {{-- Etapa 6: emitir e instalar --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Etapa 6</x-eyebrow>
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Emitir e instalar</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">No A1 · duas senhas</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">Senha de emissão, usada uma única vez. Senha de instalação, criada depois, usada em cada computador. O A1 não tem PIN. As senhas não podem ser recuperadas.</p>
                </div>
                <div class="rounded-xl border border-border bg-white p-5">
                    <div class="mb-2 font-heading text-sm font-semibold text-ink">No A3 · PIN e PUK</div>
                    <p class="font-sans text-[13px] leading-relaxed text-muted">PIN para uso diário, PUK para desbloqueio. Perdidos os dois, é preciso novo certificado e nova mídia.</p>
                </div>
            </div>
            <p class="mt-3.5 font-sans text-[13px] text-muted-light">Faça uma cópia de segurança do arquivo A1. Sem ela, computador quebrado significa novo certificado.</p>
        </div>
    </section>

    {{-- Começando a usar --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <h2 class="mb-4 font-heading text-2xl font-bold text-ink">Começando a usar</h2>
            <p class="mb-2.5 max-w-2xl font-sans text-sm leading-relaxed text-muted">Com o certificado instalado, você já pode acessar o e-CAC, assinar documentos e configurar o certificado no seu sistema de emissão de nota fiscal.</p>
            <p class="max-w-2xl font-sans text-sm leading-relaxed text-muted">A validade jurídica da assinatura digital existe apenas em meio eletrônico. Impresso, o documento perde essa validade.</p>
        </div>
    </section>

    {{-- Banco integral de perguntas: nove categorias --}}
    <section class="w-full bg-surface-alt px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto max-w-6xl">
            <x-eyebrow class="mb-2 block">Banco integral de perguntas · nove categorias</x-eyebrow>
            <h2 class="mb-4.5 font-heading text-2xl font-bold text-ink">Perguntas frequentes</h2>
            <div class="mb-5 flex flex-wrap gap-2">
                <span class="rounded-lg border border-border bg-white px-3 py-1.5 font-sans text-xs font-semibold text-ink">1 · Antes de comprar</span>
                <span class="rounded-lg border border-border bg-white px-3 py-1.5 font-sans text-xs font-semibold text-ink">2 · Compra e pagamento</span>
                <span class="rounded-lg border border-border bg-white px-3 py-1.5 font-sans text-xs font-semibold text-ink">3 · Videoconferência e validação</span>
                <span class="rounded-lg border border-border bg-white px-3 py-1.5 font-sans text-xs font-semibold text-ink">4 · Emissão e instalação</span>
                <span class="rounded-lg border border-border bg-white px-3 py-1.5 font-sans text-xs font-semibold text-ink">5 · Uso do certificado</span>
                <span class="rounded-lg border border-border bg-white px-3 py-1.5 font-sans text-xs font-semibold text-ink">6 · Renovação e vencimento</span>
                <span class="rounded-lg border border-border bg-white px-3 py-1.5 font-sans text-xs font-semibold text-ink">7 · MEI</span>
                <span class="rounded-lg border border-border bg-white px-3 py-1.5 font-sans text-xs font-semibold text-ink">8 · Revogação, garantia e devolução</span>
                <span class="rounded-lg border border-border bg-white px-3 py-1.5 font-sans text-xs font-semibold text-ink">9 · Suporte e atendimento</span>
            </div>
            <x-faq-accordion :items="[
                [
                    'pergunta' => 'Qual a diferença entre e-CPF e e-CNPJ?',
                    'resposta' => 'O e-CPF representa você como pessoa física: assina documentos, declara o Imposto de Renda e acessa serviços do governo. O e-CNPJ representa a sua empresa: emite nota fiscal, acessa o e-CAC e assina em nome do CNPJ. São Certificados Digitais independentes.',
                    'ancora' => 'diferenca-ecpf-ecnpj',
                ],
                [
                    'pergunta' => 'Qual a diferença entre A1 e A3?',
                    'resposta' => 'O A1 é um arquivo instalado no computador, vale 1 ano e não exige equipamento. O A3 fica gravado em um token USB ou cartão com leitora, vale de 1 a 3 anos e só funciona no dispositivo em que estiver conectado.',
                    'ancora' => 'diferenca-a1-a3',
                ],
                [
                    'pergunta' => 'Qual é o mais vendido?',
                    'resposta' => 'O A1 é o mais vendido, porque atende a maior parte dos casos sem exigir a compra de um equipamento.',
                    'ancora' => 'mais-vendido',
                ],
                [
                    'pergunta' => 'Sou MEI, qual eu compro?',
                    'resposta' => 'O MEI compra o e-CNPJ, o mesmo Certificado Digital usado por qualquer empresa. Não existe versão nem preço exclusivo para MEI.',
                    'ancora' => 'sou-mei-qual-compro',
                ],
                [
                    'pergunta' => 'O que é um certificado digital?',
                    'resposta' => 'É a sua identidade eletrônica. Ele permite assinar documentos, emitir nota fiscal e acessar sistemas do governo com a mesma validade jurídica de uma assinatura em papel com firma reconhecida em cartório.',
                    'ancora' => 'o-que-e-certificado-digital',
                ],
                [
                    'pergunta' => 'Posso ter o e-CPF e o e-CNPJ ao mesmo tempo?',
                    'resposta' => 'Sim. São certificados independentes, com finalidades diferentes.',
                    'ancora' => 'posso-ter-ecpf-e-ecnpj-ao-mesmo-tempo',
                ],
                [
                    'pergunta' => 'Quais formas de pagamento vocês aceitam?',
                    'resposta' => 'Por enquanto, o pagamento é feito só pelo Pix, com confirmação em poucos minutos assim que você paga.',
                    'ancora' => 'formas-de-pagamento-aceitas',
                ],
                [
                    'pergunta' => 'Recebo comprovante da compra?',
                    'resposta' => 'Sim. O comprovante e as instruções para agendar a Validação por videoconferência chegam por e-mail assim que o pagamento é confirmado.',
                    'ancora' => 'recebo-comprovante-da-compra',
                ],
                [
                    'pergunta' => 'Comprei e ainda não validei. Tenho prazo?',
                    'resposta' => 'Sim, você tem 180 dias corridos, contados da compra, para concluir a Validação por videoconferência e a emissão do certificado.',
                    'ancora' => 'comprei-e-ainda-nao-validei-tenho-prazo',
                ],
                [
                    'pergunta' => 'Posso comprar o certificado para outra pessoa usar?',
                    'resposta' => 'Sim. Quem compra e quem é o titular podem ser pessoas diferentes, mas a Validação por videoconferência é sempre feita pelo próprio titular, ou pelo responsável legal no caso de pessoa jurídica.',
                    'ancora' => 'posso-comprar-para-outra-pessoa-usar',
                ],
                [
                    'pergunta' => 'Posso autorizar outra pessoa a fazer a Validação por videoconferência no meu lugar?',
                    'resposta' => 'Não. A Validação por videoconferência é feita sempre pelo próprio titular do e-CPF, com apresentação dos documentos originais. Depois de emitido, o e-CPF pode ser usado para representar outra pessoa ou empresa como procurador, mas a validação de identidade nunca pode ser feita por terceiro.',
                    'ancora' => 'validacao-por-terceiro',
                ],
                [
                    'pergunta' => 'Meu contador pode usar o Certificado Digital por mim?',
                    'resposta' => 'Não. A Validação por videoconferência é feita pelo próprio responsável legal da empresa. Depois de emitido, o arquivo A1 pode ser entregue ao seu contador para uso em nome da empresa, mas a validação de identidade não pode ser feita por terceiro.',
                    'ancora' => 'contador-pode-usar-certificado',
                ],
                [
                    'pergunta' => 'Outra pessoa pode fazer a validação por mim?',
                    'resposta' => 'Não. A validação é feita pelo próprio titular, ou pelo responsável legal no caso de pessoa jurídica. As exceções são procuração pública lavrada em cartório, com poder específico para validação de certificado ICP-Brasil e validade de 90 dias, e curatela nos casos previstos em lei.',
                    'ancora' => 'outra-pessoa-pode-fazer-validacao',
                ],
                [
                    'pergunta' => 'Quem pode fazer a Validação por videoconferência?',
                    'resposta' => 'Quem já teve certificado digital emitido a partir de 2018, com coleta de biometria facial e digital, em qualquer Autoridade de Registro, ou tem CNH emitida ou renovada a partir de 2017.',
                    'ancora' => 'quem-pode-validar-por-videoconferencia',
                ],
                [
                    'pergunta' => 'A Validação por videoconferência tem custo?',
                    'resposta' => 'Não. A Validação por videoconferência está incluída no valor do certificado, sem taxa adicional.',
                    'ancora' => 'videoconferencia-tem-custo',
                ],
                [
                    'pergunta' => 'Quantas senhas tem o certificado?',
                    'resposta' => 'O A1 tem duas senhas: a senha de emissão, usada uma única vez, e a senha de instalação, criada depois e usada em cada computador. O A3 não usa senha, mas PIN e PUK: o PIN para uso diário e o PUK para desbloqueio.',
                    'ancora' => 'quantas-senhas-tem-o-certificado',
                ],
                [
                    'pergunta' => 'Esqueci a senha de instalação do A1. Dá para recuperar?',
                    'resposta' => 'Não. As senhas do A1 não podem ser recuperadas por ninguém, nem pela nossa equipe. Se perdeu as duas, é preciso emitir um novo certificado.',
                    'ancora' => 'esqueci-a-senha-de-instalacao-do-a1',
                ],
                [
                    'pergunta' => 'Quanto tempo leva para emitir depois da validação?',
                    'resposta' => 'Na maioria dos casos, a emissão é liberada em poucos minutos depois da Validação por videoconferência.',
                    'ancora' => 'quanto-tempo-leva-para-emitir-depois-da-validacao',
                ],
                [
                    'pergunta' => 'Preciso fazer cópia de segurança do arquivo A1?',
                    'resposta' => 'Sim. Sem uma cópia de segurança, se o computador quebrar ou o arquivo for perdido, a única solução é emitir um novo certificado.',
                    'ancora' => 'preciso-fazer-copia-de-seguranca-do-a1',
                ],
                [
                    'pergunta' => 'O A3 funciona em qualquer computador?',
                    'resposta' => 'O A3 funciona em qualquer computador com o driver do token ou leitora instalado, nos sistemas Windows e Linux. No Mac OS há restrição de versão.',
                    'ancora' => 'o-a3-funciona-em-qualquer-computador',
                ],
                [
                    'pergunta' => 'O e-CPF é a mesma coisa que a conta gov.br?',
                    'resposta' => 'Não. O e-CPF é um Certificado Digital, emitido por uma Autoridade de Registro credenciada no ICP-Brasil, usado para assinar documentos com validade jurídica. A conta gov.br é um login do governo federal; ter o e-CPF instalado é uma das formas de alcançar o nível ouro nessa conta, mas são coisas diferentes.',
                    'ancora' => 'ecpf-e-conta-gov-br',
                ],
                [
                    'pergunta' => 'Serve para assinar documento em PDF?',
                    'resposta' => 'Sim. O e-CPF assina PDF, Word e outros formatos digitais com validade jurídica equivalente à assinatura manuscrita, reconhecida por órgãos públicos e empresas privadas.',
                    'ancora' => 'assinar-documento-em-pdf',
                ],
                [
                    'pergunta' => 'Tenho empresa. Preciso do e-CPF ou do e-CNPJ?',
                    'resposta' => 'Depende do uso. O e-CPF assina em seu nome como pessoa física, inclusive como responsável legal ou procurador de uma empresa. O e-CNPJ assina em nome da própria empresa, como na emissão de nota fiscal. Muitos sócios e responsáveis legais têm o e-CPF e o e-CNPJ, conforme a necessidade.',
                    'ancora' => 'ecpf-ou-ecnpj-com-empresa',
                ],
                [
                    'pergunta' => 'Serve para o meu sistema de emissão de notas?',
                    'resposta' => 'Sim. O e-CNPJ segue o Padrão ICP-Brasil e é aceito por qualquer sistema homologado de NF-e, NFS-e ou NFC-e. No formato A1, alguns softwares de nota fiscal podem excluir o arquivo do certificado após o uso; confirme com o seu sistema qual formato ele recomenda.',
                    'ancora' => 'serve-sistema-emissao-notas',
                ],
                [
                    'pergunta' => 'Quem é o responsável pelo certificado da empresa?',
                    'resposta' => 'É o representante legal constante no ato constitutivo da empresa, ou um responsável indicado com poderes específicos. É essa pessoa que participa da Validação por videoconferência e apresenta os documentos da empresa e os seus próprios.',
                    'ancora' => 'responsavel-pelo-certificado-da-empresa',
                ],
                [
                    'pergunta' => 'Meu certificado é de outra Autoridade de Registro. Posso renovar aqui?',
                    'resposta' => 'Sim. Certificado digital não tem fidelidade a nenhuma Autoridade de Registro. Você pode renovar aqui mesmo que o certificado anterior tenha sido emitido em qualquer outra empresa credenciada no ICP-Brasil.',
                    'ancora' => 'certificado-de-outra-autoridade-posso-renovar-aqui',
                ],
                [
                    'pergunta' => 'Meu certificado já venceu. Ainda posso renovar?',
                    'resposta' => 'Sim, o processo é o mesmo. A diferença é que você fica sem certificado válido até concluir a nova emissão, já que um certificado vencido não é reativado nem renovado — é sempre emitido um certificado novo.',
                    'ancora' => 'certificado-vencido-ainda-posso-renovar',
                ],
                [
                    'pergunta' => 'Posso trocar de A1 para A3 na renovação?',
                    'resposta' => 'Sim. Você pode mudar de tipo livremente na renovação, tanto de A1 para A3 quanto de A3 para A1, conforme a sua necessidade.',
                    'ancora' => 'posso-trocar-de-a1-para-a3-na-renovacao',
                ],
                [
                    'pergunta' => 'Sou MEI, pago mais barato?',
                    'resposta' => 'Não. O MEI compra o mesmo e-CNPJ vendido para qualquer empresa, com a variação A1 pré-selecionada. Não existe SKU nem preço exclusivo para MEI.',
                    'ancora' => 'sou-mei-pago-mais-barato',
                ],
                [
                    'pergunta' => 'Preciso do certificado para abrir MEI?',
                    'resposta' => 'Não. Para abrir o MEI você só precisa do CCMEI, emitido gratuitamente no Portal do Empreendedor. O Certificado Digital entra depois, quando você precisa emitir nota fiscal ou assinar em nome do CNPJ.',
                    'ancora' => 'preciso-certificado-para-abrir-mei',
                ],
                [
                    'pergunta' => 'Sou MEI e não tenho contador. Consigo usar sozinho?',
                    'resposta' => 'Sim. Depois de emitido, o e-CNPJ pode ser usado diretamente por você para emitir nota fiscal e acessar o e-CAC, sem depender de um contador. Se preferir, o arquivo A1 também pode ser compartilhado com um contador quando você contratar um.',
                    'ancora' => 'mei-sem-contador-consigo-usar-sozinho',
                ],
                [
                    'pergunta' => 'Meu MEI está com pendência. Posso comprar?',
                    'resposta' => 'Sim, a compra e a emissão do certificado não dependem da regularidade do CNPJ. Mas pendências podem impedir o uso do certificado para algumas finalidades, como emitir nota fiscal, então recomendamos regularizar o quanto antes.',
                    'ancora' => 'mei-com-pendencia-posso-comprar',
                ],
                [
                    'pergunta' => 'Tenho direito de arrependimento?',
                    'resposta' => 'Sim, você tem 7 dias corridos a partir da aprovação do certificado para desistir da compra. A devolução do valor exige a revogação do certificado e, se houver equipamento como token, cartão ou leitora, ele deve retornar em estado de novo.',
                    'ancora' => 'tenho-direito-de-arrependimento',
                ],
                [
                    'pergunta' => 'Perdi o token do A3. Preciso revogar o certificado?',
                    'resposta' => 'Sim. Perdido o token, o certificado se perde junto. É preciso emitir um novo certificado e revogar o anterior, para evitar uso indevido.',
                    'ancora' => 'perdi-o-token-preciso-revogar',
                ],
                [
                    'pergunta' => 'A revogação pode ser desfeita?',
                    'resposta' => 'Não. A revogação é permanente. Depois de revogado, o certificado não pode voltar a ser usado, mesmo que o token seja recuperado depois.',
                    'ancora' => 'a-revogacao-pode-ser-desfeita',
                ],
                [
                    'pergunta' => 'Mudou o responsável legal da empresa. O que eu faço?',
                    'resposta' => 'É preciso emitir um novo certificado em nome do novo responsável legal.',
                    'ancora' => 'mudou-o-responsavel-legal-da-empresa',
                ],
                [
                    'pergunta' => 'Como eu falo com o suporte?',
                    'resposta' => 'Pelo WhatsApp, que é o canal mais rápido, ou por telefone e e-mail em horário comercial.',
                    'ancora' => 'como-falar-com-o-suporte',
                ],
                [
                    'pergunta' => 'O que preciso ter em mãos para o atendimento?',
                    'resposta' => 'Para agilizar, tenha em mãos o número do pedido ou o CPF ou CNPJ do titular.',
                    'ancora' => 'o-que-ter-em-maos-para-o-atendimento',
                ],
                [
                    'pergunta' => 'Não recebi o e-mail de agendamento. O que eu faço?',
                    'resposta' => 'Confira a caixa de spam e promoções. Se não estiver lá, fale com a gente que reenviamos.',
                    'ancora' => 'nao-recebi-o-email-de-agendamento',
                ],
                [
                    'pergunta' => 'O sistema não reconhece o certificado. O que eu faço?',
                    'resposta' => 'Confira a instalação e a validade do certificado. No A3, confira se o token está conectado e se o driver está instalado. Se o problema continuar, fale com o nosso suporte.',
                    'ancora' => 'o-sistema-nao-reconhece-o-certificado',
                ],
            ]" />
        </div>
    </section>

    {{-- Fechamento --}}
    <section class="w-full bg-white px-6 py-11 md:px-10 md:py-14">
        <div class="mx-auto flex max-w-6xl flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
            <h2 class="font-heading text-2xl font-bold text-ink">Pronto para começar?</h2>
            <div class="flex flex-col gap-2.5 sm:flex-row">
                <a href="/certificado-digital/e-cpf/" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Comprar e-CPF</a>
                <a href="/certificado-digital/e-cnpj/" class="rounded-lg bg-brand px-5.5 py-3 text-center font-heading text-sm font-semibold text-white whitespace-nowrap">Comprar e-CNPJ</a>
            </div>
        </div>
    </section>
</x-layout>
