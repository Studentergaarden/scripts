# Denne sql sletter beboerer der er flyttet fra alle udvalg
# og sletter ikke konsistent data fra grp_user tabellen
# netudvalget og systemadministratorer beholder deres medlemmer

# Sletter ikke konsistent data
delete from grp_user where grp_id not in ( select grp_id from grp )

# Sletter alle flyttede beboerer
delete from grp_user
where user_id in
(
select user_id from
name, user
where 
status = 'flyttet'
and user.name_id = name.name_id
)
and grp_id != 200 #Netudvalg
and grp_id != 20 #Systemadministartorer
