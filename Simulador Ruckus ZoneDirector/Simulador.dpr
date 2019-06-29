program Simulador;

{$APPTYPE CONSOLE}

uses
  System.SysUtils,
  System.Classes,
  System.Character,
  System.RegularExpressions,
  Winapi.Windows;

type
  PUserData = ^TUserData;
  TUserData = record
    id: integer;
    userName, fullName, password, role: string;
  end;
  PRoleData = ^TRoleData;
  TRoleData = record
    id, name, description, attributes: string;
    guestPass, status, mode, policy, wlan: string;
  end;

function OccurrencesOfChar(const ContentString: string;
  const CharToCount: char): integer;
var
  C: Char;
begin
  result := 0;
  for C in ContentString do
    if C = CharToCount then
      Inc(result);
end;

procedure saveUsersToTxtFile(userList: TList);
var
  I: Integer;
  user: PUserData;
  data: TStringList;
begin
  try
    try
      data := TStringList.Create;
      for I := 0 to (userList.Count-1) do
        with PUserData(userList[I])^ do
          data.Add(IntToStr(id)+';'+userName+';'+fullName+';'+password+';'+role);
      data.WriteBOM := false;
      data.SaveToFile(ExtractFilePath(ParamStr(0))+'users.txt', TEncoding.UTF8);
    finally
      data.Free;
    end;
  except
    on E: Exception do
      Writeln(E.ClassName, ': ', E.Message);
  end;
end;

function loadUsersFromTxtFile: TList;
var
  I: Integer;
  user: PUserData;
  data, aux: TStringList;
begin
  try
    try
      data := TStringList.Create;
      aux := TStringList.Create;
      data.LoadFromFile(ExtractFilePath(ParamStr(0))+'users.txt', TEncoding.UTF8);
      Result := TList.Create;
      for I := 0 to (data.Count-1) do
      begin
        aux.Clear;
        aux.StrictDelimiter := true;
        aux.Delimiter := ';';
        aux.DelimitedText := data[i];
        New(user);
        user^.ID       := StrToInt(aux[0]);
        user^.userName := aux[1];
        user^.fullName := aux[2];
        user^.password := aux[3];
        user^.role     := aux[4];
        Result.Add(user);
      end;
    finally
      data.Free;
      aux.Free;
    end;
  except
    on E: Exception do
      Writeln(E.ClassName, ': ', E.Message);
  end;
end;

function loadRolesFromTxtFile: TList;
var
  I: Integer;
  role: PRoleData;
  data, aux: TStringList;
begin
  try
    try
      data := TStringList.Create;
      aux := TStringList.Create;
      data.LoadFromFile(ExtractFilePath(ParamStr(0))+'roles.txt', TEncoding.UTF8);
      Result := TList.Create;
      for I := 0 to (data.Count-1) do
      begin
        aux.Clear;
        aux.StrictDelimiter := true;
        aux.Delimiter := ';';
        aux.DelimitedText := data[i];
        New(role);
        role^.id := aux[0];
        role^.name := aux[1];
        role^.description := aux[2];
        role^.attributes := aux[3];
        role^.guestPass := aux[4];
        role^.status := aux[5];
        role^.mode := aux[6];
        role^.wlan := aux[7];
        role^.policy := aux[8];
        Result.Add(role);
      end;
    finally
      data.Free;
      aux.Free;
    end;
  except
    on E: Exception do
      Writeln(E.ClassName, ': ', E.Message);
  end;
end;

procedure printRoleData(roleList: TList);
var I: integer;
begin
  Writeln('Role:');
  Writeln('  ID:');
  for I := 0 to (roleList.Count-1) do
  begin
    with (PRoleData(roleList.Items[i])^) do
    begin
      Writeln('    '+id+':');
      Writeln('      Name= '+name);
      Writeln('      Description= '+description);
      Writeln('      Group Attributes= '+attributes);
      Writeln('      Guest Pass Generation= '+guestPass);

      Writeln('      ZoneDirector Administration:');
      Writeln('        Status= '+status);
      Writeln('      Allow All WLANs:');
      Writeln('        Mode= '+mode);
      if wlan <> '' then
        Writeln('        Wlan= '+wlan);
      Writeln('      Access Control Policy= '+policy);
      Writeln('');
    end;
  end;
end;

procedure printUserData(user: PUserData);
begin
  with (user^) do
  begin
    Writeln('    '+IntToStr(id)+':');
    Writeln('      User Name= '+userName);
    Writeln('      Full Name= '+fullName);
    Writeln('      Password= ********');
    Writeln('      Role= '+role);
    Writeln('');
  end;
end;

function getUserIndex(userList: TList; uName: string): Integer;
var I: Integer;
begin
  Result := -1;
  for I := 0 to (userList.Count-1) do
  begin
    if PUserData(userList.Items[I])^.userName = uName then
    begin
      Result := I;
      exit;
    end;
  end;
end;

function userNameInUse(userList: TList; uId: integer; uName: string): boolean;
var I: Integer;
begin
  Result := false;
  for I := 0 to (userList.Count-1) do
    with (PUserData(userList.Items[I])^) do
      if (userName = uName) and (id <> uId) then
      begin
        Result := true;
        exit;
      end;
end;

function roleExists(roleList: TList; name: string): boolean;
var I: integer;
begin
  Result := false;
  for I := 0 to (roleList.Count-1) do
    if PRoleData(roleList.Items[I])^.name = name then
    begin
      Result := true;
      exit;
    end;
end;

procedure print (responseType, param: string);
begin
  if responseType = 'wellcome' then
    Writeln('Welcome to the Ruckus Wireless ZoneDirector 3000 Command Line Interface');
  if responseType = 'have_all_rights' then
    Writeln('You have all rights in this mode.');
  if responseType = 'not_saved' then
    Writeln('No changes have been saved.');
  if responseType = 'saved' then
    WriteLn('Your changes have been saved.');
  if responseType = 'command_error' then
    Writeln('The command is either unrecognized or incomplete. To view a list of commands that you can run from this context, type ''?'' or ''help''.');
  if responseType = 'role_error' then
    writeln('The Role could not be found. Please check the spelling, and then try again.');
  if responseType = 'save_error' then
    Writeln('The operation doesn''t execute successfully. Please try again.');
  if responseType = 'userName_exists' then
    writeln('O nome ['+param+'] já existe. Por favor entre um nome diferente.');
  if responseType = 'userName_error' then
    writeln('The User name can only contain up to 32 alphanumeric characters, underscores(_), periods (.), and cannot start with a number.');
  if responseType = 'fullName_error' then
    writeln('The User full name must be between 1 and 32 characters.');
  if responseType = 'password_error' then
    writeln('The User password can only contain between 4 and 32 characters,including characters from !(char 33) to ~(char 126).');
  if responseType = 'command_success' then
    Writeln('The command was executed successfully. To save the changes, type ''end'' or ''exit''.');
  if responseType = 'user_loaded' then
    Writeln('The User entry '''+param+''' has been loaded. To save the Role entry, type end or exit.');
  if responseType = 'user_created' then
    Writeln('The User entry '''+param+''' has been created.');
  if responseType = 'user_saved' then
    Writeln('The User entry has saved successfully.');
  if responseType = 'user_not_found' then
    Writeln('The entry '''+param+''' could not be found. Please check the spelling, and then try again.');
  if responseType = 'user_deleted' then
    Writeln('The User '''+param+''' has been deleted.');
end;

var
  cmd: string;
  cmds: TStringList;
  userList, roleList: TList;
  I, index: Integer;
  user: PUserData;
  super: boolean;
begin
  try
    try
      userList := loadUsersFromTxtFile;
      roleList := loadRolesFromTxtFile;
      print('wellcome', '');
      cmds := TStringList.Create;
      repeat
        Write('ruckus> ');
        Readln(cmd);
        if cmd = 'enable force' then
        begin
          repeat
            Write('ruckus# ');
            Readln(cmd);
            cmds.Clear;
            ExtractStrings([' '], [], PChar(cmd), cmds);
            if cmds.Count > 0 then
            begin
              if cmds[0] = 'config' then
              begin
                print('have_all_rights', '');
                repeat
                  Write('ruckus(config)# ');
                  Readln(cmd);
                  cmds.Clear;
                  ExtractStrings([' '], [], PChar(cmd), cmds);
                  if cmds.Count > 0 then
                  begin
                    if cmds[0] = 'no' then
                    begin
                      if cmds.Count > 1 then
                      begin
                        if cmds[1] = 'user' then
                        begin
                          if cmds.Count = 3 then
                          begin
                            index := getUserIndex(userList, cmds[2]);
                            if index > -1 then
                            begin
                              userList.Delete(index);
                              print('user_deleted', cmds[2]);
                            end
                            else
                              print('user_not_found', cmds[2]);
                          end
                          else
                            print('command_error', '');
                        end
                        else
                          print('command_error', '');
                      end
                      else
                        print('command_error', '');
                    end
                    else if cmds[0] = 'user' then
                    begin
                      if cmds.Count > 1 then
                      begin
                        if TRegEx.IsMatch(cmds[1], '^[a-zA-Z|\.|_]+[a-zA-Z0-9|\.|_]*$')
                          and (Length(cmds[1]) < 33)then
                        begin
                          New(user);
                          index := getUserIndex(userList, cmds[1]);
                          if index <> -1 then
                          begin
                            with (PUserData(userList.Items[index])^) do
                            begin
                              user^.id := id;
                              user^.userName := userName;
                              user^.fullName := fullName;
                              user^.password := password;
                              user^.role := role;
                            end;
                            print('user_loaded', cmds[1]);
                          end
                          else
                          begin
                            if userList.Count > 0 then
                              user^.ID := PUserData(userList.Items[userList.Count-1])^.id + 1
                            else
                              user^.ID := 1;
                            user^.userName := cmds[1];
                            user^.role := 'Default';
                            print('user_created', cmds[1]);
                          end;
                        end
                        else
                        begin
                          print('userName_error', '');
                          Continue;
                        end;
                        repeat
                          Write('ruckus(config-user)# ');
                          Readln(cmd);
                          cmds.Clear;
                          ExtractStrings([' '], [], PChar(cmd), cmds);
                          if cmds.Count > 0 then
                          begin
                            if cmds[0] = 'show' then
                            begin
                              Writeln('User:');
                              Writeln('  ID:');
                              printUserData(user);
                            end
                            else if cmds[0] = 'user-name' then
                            begin
                              if cmds.Count = 2 then
                              begin
                                if TRegEx.IsMatch(cmds[1], '^[a-zA-Z|/.|_]+[a-zA-Z0-9|\.|_]*$')
                                  and (Length(cmds[1]) < 33) then
                                begin
                                  user^.userName := cmds[1];
                                  print('command_success', '');
                                end
                                else
                                  print('userName_error', '');
                              end
                              else
                                print('command_error', '');
                            end
                            else if cmds[0] = 'full-name' then
                            begin
                              if cmds.Count > 1 then
                              begin
                                cmds[1] := Copy(cmd, 11, (Length(cmd)-10));
                                if TRegEx.IsMatch(cmds[1], '^''.*''$') then
                                begin
                                  cmds[1] := Copy(cmds[1], 2, (Length(cmds[1])-2));
                                  if TRegEx.IsMatch(cmds[1], '^[\x20\x30-\x39\x40-\x7A]{1,34}$') then
                                  begin
                                    user^.fullName := cmds[1];
                                    print('command_success', '');
                                  end
                                  else
                                    print('fullName_error', '');
                                end
                                else if not TRegEx.IsMatch(cmds[1], '[\s]+') then
                                begin
                                  if TRegEx.IsMatch(cmds[1], '^[\x30-\x39\x40-\x7A]{1,34}$') then
                                  begin
                                    user^.fullName := cmds[1];
                                    print('command_success', '');
                                  end
                                  else
                                    print('fullName_error', '');
                                end
                                else
                                  print('command_error', '');
                              end
                              else
                                print('command_error', '');
                            end
                            else if cmds[0] = 'password' then
                            begin
                              if cmds.Count > 1 then
                              begin
                                if TRegEx.IsMatch(cmds[1], '^[\x21-\x7E]{4,32}$') then
                                begin
                                  user^.password := cmds[1];
                                  print('command_success', '');
                                end
                                else
                                  print('password_error', '');
                              end
                              else
                                print('command_error', '');
                            end
                            else if cmds[0] = 'role' then
                            begin
                              if cmds.Count = 2 then
                              begin
                                if roleExists(roleList, cmds[1]) then
                                begin
                                  user^.role := cmds[1];
                                  print('command_success', '');
                                end
                                else
                                  print('role_error', '');
                              end
                              else
                                print('command_error', '');
                            end
                            else if (cmd = 'exit') or (cmd = 'end') then
                            begin
                              if user^.password = '' then
                              begin
                                print('save_error', '');
                                cmd := '';
                              end
                              else
                              begin
                                if userNameInUse(userList, user^.id, user^.userName) then
                                begin
                                  print('userName_exists', user^.userName);
                                  cmd := '';
                                end
                                else
                                begin
                                  if index = -1 then
                                    userList.Add(user)
                                  else
                                  begin
                                    with userList do
                                    begin
                                      PUserData(Items[index])^.userName := user^.userName;
                                      PUserData(Items[index])^.fullName := user^.fullName;
                                      PUserData(Items[index])^.password := user^.password;
                                      PUserData(Items[index])^.role := user^.role;
                                    end;
                                  end;
                                  saveUsersToTxtFile(userList);
                                  print('user_saved', '');
                                  print('saved', '');
                                end;
                              end;
                            end
                            else if (cmd = 'abort') or (cmd = 'quit') then
                            begin
                              print('not_saved', '');
                            end else
                              print('command_error', '');
                          end;
                        until (cmd = 'exit') or (cmd = 'end') or (cmd = 'abort') or (cmd = 'quit');
                        cmd := '';
                      end
                      else
                        print('command_error', '');
                    end
                    else if (cmd = 'exit') or (cmd = 'end') then
                    begin
                      saveUsersToTxtFile(userList);
                      print('saved', '');
                    end
                    else
                    begin
                      if (cmd = 'abort') or (cmd = 'quit') then
                        print('not_saved', '')
                      else
                        print('command_error', '');
                    end;
                  end
                until (cmd = 'exit') or (cmd = 'end') or (cmd = 'abort') or (cmd = 'quit');
                cmd := '';
              end
              else if cmds[0] = 'show' then
              begin
                if cmds.Count > 1 then
                begin
                  if cmds[1] = 'user' then
                  begin
                    if cmds.Count > 2 then
                    begin
                      if (cmds[2] = 'all') and (cmds.Count = 3) then
                      begin
                        Writeln('User:'+#13#10+'  ID:');
                        for I := 0 to (userList.Count-1) do
                          printUserData(userList.Items[I]);
                      end
                      else if cmds[2] = 'name' then
                      begin
                        if cmds.Count = 4 then
                        begin
                          index := getUserIndex(userList, cmds[3]);
                          if index > -1 then
                          begin
                            Writeln('User:'+#13#10+'  ID:');
                            printUserData(userList.Items[index]);
                          end
                          else
                            print('user_not_found', cmds[3]);
                        end
                        else
                          print('command_error', '');
                      end
                      else
                        print('command_error', '');
                    end
                    else
                      print('command_error', '');
                  end
                  else if cmds[1] = 'role' then
                  begin
                    if cmds.Count = 3 then
                    begin
                      if cmds[2] = 'all' then
                      begin
                        printRoleData(roleList);
                      end
                      else
                        print('command_error', '');
                    end
                    else
                      print('command_error', '');
                  end
                  else
                    print('command_error', '');
                end
                else
                  print('command_error', '');
              end
              else if ((cmd<>'exit') and (cmd<>'quit')) then
              begin
                print('command_error', '');
              end;
            end;
          until (cmd = 'exit') or (cmd = 'end');
        end
        else if (cmd<>'exit') and (cmd<>'quit') then
        begin
          print('command_error', '');
        end;
      until (cmd = 'exit') or (cmd = 'end');
    except
      on E: Exception do
        Writeln(E.ClassName, ': ', E.Message);
    end;
  finally
    userList.Free;
  end;
end.
