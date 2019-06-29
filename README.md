# ruckus-zonedirector-user-mngr

Sistema web capaz de gerenciar a base de dados interna de usuários de controladores Ruckus Wireless ZoneDirector, proporcionando recursos adicionais os quais não estão disponíveis na interface web nem na interface de linha de comando desses equipamentos.

O arquivo “ZoneDirectorUserManager.php” se comunica através da extensão SSH2 para PHP com o controlador ZoneDirector ou outro servidor SSH que execute o arquivo “Simulador.exe”.

Para simular o funcionamento do controlador, pode ser usado a programa Bitvise SSH Server, o qual cria um servidor SSH no sistema operacional Windows e pode ser configurado para executar o arquivo “Simulador.exe” para responder as entradas na linha de comando.
O executável “Simulador.exe” foi desenvolvido em Delphi e simula o comportamento da interface de linha de comando do controlador ZoneDirector restringindo-se à parte de gerenciamento dos usuários contidos na base de dados interna. Os dados dos usuários e as regras são armazenas e recuperas pelo executável respectivamente nos arquivos “users.txt” e “roles.txt”.

