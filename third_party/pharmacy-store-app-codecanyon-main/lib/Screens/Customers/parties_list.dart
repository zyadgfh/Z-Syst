import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:mobile_pos/Screens/Customers/Provider/customer_provider.dart';
import 'package:mobile_pos/Screens/Customers/party_details.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:nb_utils/nb_utils.dart';
import '../../currency.dart';
import 'add_party.dart';
import 'package:mobile_pos/Screens/Customers/Model/parties_model.dart';

class PartyList extends StatefulWidget {
  const PartyList({super.key});

  @override
  State<PartyList> createState() => _PartyListState();
}

class _PartyListState extends State<PartyList> {
  @override
  Widget build(BuildContext context) {
    final language = lang.S.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        title: Text(
          lang.S.of(context).partyList,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(color: kWhite, fontWeight: FontWeight.w600, fontSize: 20),
        ),
        centerTitle: true,
        iconTheme: const IconThemeData(color: kWhite),
        elevation: 0.0,
      ),
      body: DefaultTabController(
        length: 4,
        child: Consumer(builder: (context, ref, __) {
          return Column(
            children: [
              Container(
                decoration: const BoxDecoration(color: kMainColorBg, borderRadius: BorderRadius.only(topRight: Radius.circular(30), topLeft: Radius.circular(30))),
                child: TabBar(
                    isScrollable: true,
                    dividerColor: Colors.transparent,
                    tabAlignment: TabAlignment.start,
                    indicatorSize: TabBarIndicatorSize.tab,
                    indicatorWeight: 2,
                    unselectedLabelStyle: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w500, color: kNutral700),
                    labelStyle: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500, color: kMainColor),
                    tabs: [
                      Tab(text: language.allParties),
                      Tab(text: language.retailer),
                      Tab(text: language.wholesaler),
                      Tab(text: language.supplier),
                    ]),
              ),
              Expanded(
                child: TabBarView(children: [
                  _buildFilteredPartyList(ref, 'All Parties'),
                  _buildFilteredPartyList(ref, 'Retailer'),
                  _buildFilteredPartyList(ref, 'Wholesaler'),
                  _buildFilteredPartyList(ref, 'Supplier'),
                ]),
              ),
            ],
          );
        }),
      ),
      bottomNavigationBar: Padding(
        padding: const EdgeInsets.all(10.0),
        child: ElevatedButton.icon(
            icon: const Icon(
              Icons.add,
              color: kWhite,
            ),
            style: ElevatedButton.styleFrom(backgroundColor: kMainColor),
            onPressed: () => const AddParty().launch(context),
            label: Text(
              language.addParties,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600, color: kWhite),
            )),
      ),
    );
  }
}

Widget _buildFilteredPartyList(WidgetRef ref, String partyType) {
  return Consumer(
    builder: (context, ref, __) {
      final providerData = ref.watch(partiesProvider);
      Color getPartyColor(String type) {
        const partyColors = {
          'retailer': Color(0xFF56da87),
          'wholesaler': Color(0xFF25a9e0),
          'supplier': Color(0xFFA569BD),
        };
        return partyColors[type.toLowerCase()] ?? Colors.white;
      }

      // Helper function for avatar
      Widget buildAvatar(PartyModel party) {
        return CircleAvatar(
          backgroundColor: Colors.white,
          radius: 25.0,
          child: ClipOval(
            child: party.image != null
                ? Image.network(
                    '${APIConfig.domain}${party.image}',
                    fit: BoxFit.cover,
                    width: 40.0,
                    height: 40.0,
                  )
                : Container(
                    alignment: Alignment.center,
                    height: 40,
                    width: 40,
                    decoration: const BoxDecoration(
                      shape: BoxShape.circle,
                      color: kMainColor,
                    ),
                    child: Text(
                      ((party.name?.substring(0, 2)) ?? party.phone?.substring(0, 2) ?? '').toUpperCase(),
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            color: kWhite,
                            fontWeight: FontWeight.w500,
                          ),
                    ),
                  ),
          ),
        );
      }

      return providerData.when(
        data: (customers) {
          final filteredList = partyType == 'All Parties' ? customers : customers.where((party) => party.type == partyType).toList();

          return filteredList.isNotEmpty
              ? ListView.builder(
                  padding: EdgeInsets.zero,
                  itemCount: filteredList.length,
                  itemBuilder: (_, index) {
                    final party = filteredList[index];
                    final color = getPartyColor(party.type ?? '');

                    return ListTile(
                      onTap: () => CustomerDetails(party: party).launch(context),
                      contentPadding: EdgeInsets.symmetric(horizontal: 16),
                      leading: SizedBox(
                        height: 50.0,
                        width: 50.0,
                        child: buildAvatar(party),
                      ),
                      title: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Flexible(
                            child: Text(
                              party.name ?? party.phone!,
                              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                                    color: kNutrals900,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                            ),
                          ),
                          SizedBox(width: 10),
                          // if (party.due != null && party.due != 0)
                          Text(
                            (party.due != null && party.due != 0) ? '$currency${party.due?.toStringAsFixed(2)}' : '$currency${0}',
                            style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                                  color: kNutrals900,
                                ),
                          ),
                        ],
                      ),
                      subtitle: Row(
                        children: [
                          Text(
                            party.type ?? 'n/a',
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(color: color),
                          ),
                          const Spacer(),
                          // if (party.due != null && party.due != 0)
                          Text(
                            (party.due != null && party.due != 0) ? lang.S.of(context).due : 'No Due',
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(color: kSubTitleColor, fontSize: 12, fontWeight: FontWeight.w400),
                          ),
                        ],
                      ),
                      trailing: const Icon(
                        Icons.arrow_forward_ios,
                        color: kNutral700,
                        size: 16,
                      ),
                    );
                  },
                )
              : Center(
                  child: EmptyListWidget(title: lang.S.of(context).listIsEmpty),
                );
        },
        error: (e, stack) => Text(e.toString()),
        loading: () => const Center(child: CircularProgressIndicator()),
      );
    },
  );
}
